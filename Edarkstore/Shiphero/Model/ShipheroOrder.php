<?php
namespace Edarkstore\Shiphero\Model;
use Edarkstore\Shiphero\Api\ShipheroOrderInterface;
use Magento\Sales\Model\Convert\Order;
use Magento\Shipping\Model\ShipmentNotifier;
use Edarkstore\Shiphero\Helper\Data as DataHelper;
use Psr\Log\LoggerInterface;

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

    protected $convertOrder;

    protected $shipmentNotifier;

    protected $dataHelper;

    protected $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        LoggerInterface $logger,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        Order $convertOrder,
        DataHelper $dataHelper,
        
        ShipmentNotifier $shipmentNotifier
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_invoiceService  = $invoiceService;
        $this->_transaction     = $transaction;
        $this->_trackFactory    = $trackFactory;
        $this->convertOrder     = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
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
     * @param \Edarkstore\Shiphero\Api\ShipheroLineItemInterface[] $items Line items.
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

            $convtOrder = $this->convertOrder;
            $shipment = $convtOrder->toShipment($order);

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

                $shipmentItem = $convtOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

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
                $shipment->getExtensionAttributes()->setSourceCode($this->dataHelper->getWarehouseCode());
                $shipment->addTrack($track)->save();

                $shipment->getOrder()->setIsInProcess(true);

                $shipment->save();
                $shipment->getOrder()->save();

                if ($notify_customer == 1) {
                    $this->shipmentNotifier->notify($shipment);
                }

                $shipment->save();

            } catch (\Exception $e) {
                $this->logger->debug("An error ocurred when shipping: ". $e->getMessage());
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
