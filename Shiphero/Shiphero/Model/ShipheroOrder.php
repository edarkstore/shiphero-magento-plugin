<?php
namespace Shiphero\Shiphero\Model;
use Shiphero\Shiphero\Api\ShipheroOrderInterface;

class ShipheroOrder implements ShipheroOrderInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_trackFactory = $trackFactory;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
    * Generate an invoice for an order
    *
    * @api
    * @param int Order id.
    * @return string Response status.
    */
    public function invoice($id) {

        try {
            $order = $this->_orderRepository->get($id);
        } catch (\Exception $e) {
            return "Can't fin order with id: $id";
        }

        if ($order->canInvoice()) {

            try {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();

                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionSave->save();

            } catch (\Exception $e) {
                return "An error ocurred when invoicing: ". $e->getMessage();
            }

            return "Ok";
        }

        return "Can't invoice";
    }

    private function getItemFromData($orderItem, $items) {

        foreach ($items as $item) {
            if ($item->getId() == $orderItem->getId()) {
                return $item;
            }
        }
        return null;
    }

    private function setOrderAsCompleted($order) {

        $order->setState("complete")->setStatus("complete");
        $order->save();
    }

    /**
    * Generate a shipment for an order
    *
    * @api
    * @param int $id Order id.
    * @param \Shiphero\Shiphero\Api\ShipheroLineItemInterface[] $items Line items.
    * @param string $tracking_number
    * @param string $shipping_carrier
    * @param string $shipping_method
    * @param int $notify_customer
    * @param int $set_as_completed
    * @return string Response status.
    */
    public function ship($id, $items, $tracking_number, $shipping_carrier, $shipping_method, $notify_customer, $set_as_completed) {

        try {
            $order = $this->_orderRepository->get($id);
        } catch (\Exception $e) {
            return "Can't fin order with id: $id";
        }

        if ($order->canShip()) {

            $convertOrder = $this->_objectManager->create('Magento\Sales\Model\Convert\Order');
            $shipment = $convertOrder->toShipment($order);

            foreach ($order->getAllItems() AS $orderItem) {

                $itemData = $this->getItemFromData($orderItem, $items);

                if (!$itemData) {
                    continue;
                }

                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }

                $totalQty = $orderItem->getQtyToShip();

                if ($totalQty < $itemData->getQty()) {
                    $qtyShipped = $totalQty;
                } else {
                    $qtyShipped = $itemData->getQty();
                }

                $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

                $shipment->addItem($shipmentItem);
            }

            try {

                $shipment->register();

                $data = array(
                    'carrier_code' => $shipping_carrier,
                    'title' => $shipping_method,
                    'number' => $tracking_number,
                );

                $track = $this->_trackFactory->create()->addData($data);
                $shipment->addTrack($track)->save();

                $shipment->getOrder()->setIsInProcess(true);

                $shipment->save();
                $shipment->getOrder()->save();

                if ($notify_customer == 1) {
                    $this->_objectManager->create('Magento\Shipping\Model\ShipmentNotifier')->notify($shipment);
                }

                $shipment->save();

            } catch (\Exception $e) {
                return "An error ocurred when shipping: ". $e->getMessage();
            }

            if ($set_as_completed == 1) {
                $this->setOrderAsCompleted($order);
            }

            return "Ok";
        }

        return "Can't ship";
    }

    /**
    * Set an order as completed
    *
    * @api
    * @param int $id Order id.
    * @return string Response status.
    */
    public function complete($id) {

        try {
            $order = $this->_orderRepository->get($id);
        } catch (\Exception $e) {
            return "Can't fin order with id: $id";
        }

        $this->setOrderAsCompleted($order);
        return "Ok";
    }
}