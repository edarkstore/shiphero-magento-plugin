<?php

namespace Shiphero\Shiphero\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use  Magento\Sales\Api\OrderRepositoryInterface;
use Shiphero\Shiphero\Helper\Data as DataHelper;

class ShipheroOrderObserver implements ObserverInterface

{

    protected $logger;
    protected $orderRepository;
    protected $dataHelper;
    protected $curl;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl,
        LoggerInterface $logger,
        OrderRepositoryInterface   $orderRepository,
        DataHelper      $dataHelper
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->orderRepository  = $orderRepository;
        $this->dataHelper = $dataHelper;
    }

    public function makeRequest($data)
    {
        try{
            $url = $this->dataHelper->getEndpointOrder();
            $this->curl->setOption(CURLOPT_HEADER, false);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->logger->debug('makeRequest');
            $this->logger->debug(print_r($data, true));
            $this->logger->debug("Url: ". $url);
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

            if ($data["name"] == "admin_sales_order_address_update") {
                $this->logger->debug('admin_sales_order_address_update');
                try {
                    $order = $this->orderRepository->get($event['order_id']);
                    $this->logger->debug(json_encode($order->getData()));
                } catch (\Exception $e) {
                    $this->logger->debug("Can't fin order with id: ". $event['order_id']);
                    $this->logger->debug(print_r($e->getMessage(), true));
                    return;
                }
                $orderId = $order->getId();
                $storeUrl = $order->getStore()->getBaseUrl();
            } else if ($data["name"] == "sales_model_service_quote_submit_success") {
                $order = $data["order"];
                $orderId = $order->getId();
                $storeUrl = $order->getStore()->getBaseUrl();
            } else {
                $this->logger->debug("Data name: ". $data["name"]);
                return;
            }
            $data = array(
                "source" => "magento_2",
                "topic" => "order-save",
                "extension_version" => "1.3.0",
                "body" => array(
                    "order_id" => $orderId,
                    "store_url" => $storeUrl,
                ),
            );
            $this->logger->debug('request');
            $this->logger->debug(print_r($data, true));

            $this->makeRequest($data);
        }
    }
}
