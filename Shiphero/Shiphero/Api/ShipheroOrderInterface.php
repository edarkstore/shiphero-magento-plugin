<?php
namespace Shiphero\Shiphero\Api;

interface ShipheroOrderInterface
{
    /**
    * Generate an invoice for an order
    *
    * @api
    * @param int Order id.
    * @return string Response status.
    */
    public function invoice($id);

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
    public function ship($id, $items, $tracking_number, $shipping_carrier, $shipping_method, $notify_customer, $set_as_completed);

    /**
    * Set an order as completed
    *
    * @api
    * @param int $id Order id.
    * @return string Response status.
    */
    public function complete($id);
}