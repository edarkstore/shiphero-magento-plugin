<?php

namespace Shiphero\Shiphero\Observer;

use Magento\Framework\Event\ObserverInterface;

class ShipheroOrderObserver implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {

        $this->host = "https://api-gateway.shiphero.com/v1/magento2/webhooks/orders";
        $this->url = $this->host;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->_orderRepository = $orderRepository;
    }

    public function makeRequest($data)
    {
        try{
            $url = $this->url;
            $this->curl->setOption(CURLOPT_HEADER, false);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->addHeader("Content-Type", "application/json");
            $this->logger->debug('makeRequest');
            $this->logger->debug(print_r($data, true));
            $this->curl->post($url, $data);
        }catch(\Exception $e){
            $this->logger->error($e->getMessage());
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $data = $event->getData();

        if ($data["name"] == "admin_sales_order_address_update") {
            $this->logger->debug('admin_sales_order_address_update');
            try {
                $order = $this->_orderRepository->get($event['order_id']);
                $this->logger->debug(print_r($order->getBillingAddress()->getStreet(), true));
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

        $this->makeRequest($data);
    }
}





