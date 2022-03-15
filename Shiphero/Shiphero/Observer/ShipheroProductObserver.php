<?php

namespace Shiphero\Shiphero\Observer;

use Magento\Framework\Event\ObserverInterface;


class ShipheroProductObserver implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger
    ) {

        $this->host = "https://api-gateway.shiphero.com/v1/magento2/webhooks/products";
        $this->url = $this->host;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function makeRequest($data)
    {
        try{
            $url = $this->url;
    
            $this->curl->setOption(CURLOPT_HEADER, false);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
    
            $this->curl->addHeader("Content-Type", "application/json");
    
            $this->curl->post($url, $data);
        }catch(\Exception $e){
            $this->logger->error($e->getMessage());
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
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
            "source" => "magento_2",
            "topic" => "product-save",
            "extension_version" => "1.3.0",
            "body" => array(
                "product_sku" => $product->getSku(),
                "store_url" => $storeUrl,
            ),
        );

        $this->makeRequest($data);
    }

}