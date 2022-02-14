<?php

namespace Shiphero\Shiphero\Observer;

use Magento\Framework\Event\ObserverInterface;


class ShipheroOrderObserver implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->host = "https://api-gateway.shiphero.com/v1/magento2/webhooks/orders";
        $this->url = $this->host;
    }

    public function makeRequest($data)
    {
        $url = $this->url;
        $content = json_encode($data);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            array("Content-type: application/json")
        );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status != 200)
        {
            $error_msg = "Call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", culr_errno " . curl_errno($curl);

            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->error(
                "ObserverError: " . $error_msg
            );
        }

        curl_close($curl);
    }

    private function getOrder($orderId) {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        return $order;
    }

    private function admin_sales_order_address_update($data) {

        $order = $this->getOrder($data["order_id"]);
        return $order;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $data = $event->getData();

        if ($data["name"] == "admin_sales_order_address_update") {
            $order = $this->admin_sales_order_address_update($event);
            $orderId = $order->getId();

        } else if ($data["name"] == "sales_model_service_quote_submit_success") {
            $order = $data["order"];
            $orderId = $order->getId();

        } else {
            return;
        }

        $storeUrl = $order->getStore()->getBaseUrl();

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