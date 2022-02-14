<?php
namespace Shiphero\Shiphero\Api;

/**
 * Interface for line items.
 */
interface ShipheroLineItemInterface {
    /**
     * Gets the id.
     *
     * @api
     * @return int
     */
    public function getId();

    /**
     * Sets the id.
     *
     * @api
     * @param int $id
     * @return void
     */
    public function setId($id);

    /**
     * Gets the quantity.
     *
     * @api
     * @return int
     */
    public function getQty();

    /**
     * Sets the quantity.
     *
     * @api
     * @param int $qty
     * @return void
     */
    public function setQty($qty);
}