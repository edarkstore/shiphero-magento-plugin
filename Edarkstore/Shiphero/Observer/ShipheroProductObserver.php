<?php

namespace Edarkstore\Shiphero\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Edarkstore\Shiphero\Helper\Data as DataHelper;

class ShipheroProductObserver implements ObserverInterface
{

    protected $logger;
    protected $dataHelper;
    protected $curl;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        \Magento\Framework\HTTP\Client\Curl $curl,
        DataHelper $dataHelper

    ) {
        $this->logger= $logger;
        $this->curl = $curl;
        $this->dataHelper = $dataHelper;
    }

    public function makeRequest($data)
    {
        try{
            $url = $this->dataHelper->getEndpointProduct();

            $this->curl->setOption(CURLOPT_HEADER, false);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);

            $this->curl->addHeader("Content-Type", "application/json");
            $this->logger->debug(print_r($data, true));
            $this->curl->post($url, $data);
        }catch(\Exception $e){
            $this->logger->error($e->getMessage());
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->dataHelper->isEnable()) {
            $event = $observer->getEvent();
            $data = $event->getData();

            if ($data["name"] == "catalog_product_save_after") {
                $product = $event->getProduct();

            } else if ($data["name"] == "catalog_product_delete_after") {

                $product = $event->getProduct();

            } else {
                return;
            }

            $storeUrl = $product->getStore()->getBaseUrl();

            $data = array(
                "product_sku" => $product->getSku(),
                "store_url" => $storeUrl
            );

            $this->makeRequest(json_encode($data));
        }
    }

}
