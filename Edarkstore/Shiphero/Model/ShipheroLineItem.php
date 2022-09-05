<?php
namespace Edarkstore\Shiphero\Model;
use \Edarkstore\Shiphero\Api\ShipheroLineItemInterface;

/**
 * ShipheroLineItem Model
 */
class ShipheroLineItem implements ShipheroLineItemInterface {

    /**
     * The id for this cart entry.
     * @var string
     */
    protected $id;

    /**
     * The quantity value for this cart entry.
     * @var int
     */
    protected $qty;

    /**
     * Gets the id.
     *
     * @api
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the id.
     *
     * @api
     * @param int $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Gets the quantity.
     *
     * @api
     * @return int
     */
    public function getQty() {
        return $this->qty;
    }

    /**
     * Sets the quantity.
     *
     * @api
     * @param int $qty
     * @return void
     */
    public function setQty($qty) {
        $this->qty = $qty;
    }
}