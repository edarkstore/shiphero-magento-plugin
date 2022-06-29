<?php

namespace Shiphero\Shiphero\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;



/**
 * Class Data
 * @package Formax\Omnix\Helper
 */
class Data extends AbstractHelper
{
    protected $scopeConfig;
    protected $configModule;
    protected $storeManager;
    const ENDPOINT_ORDER                = "shiphero/general/endpoint_order";
    const ENDPOINT_PRODUCT              = "shiphero/general/endpoint_product";
    const WAREHOUSE_CODE                = "shiphero/general/warehouse_code";
    const ACTIVE_MODULE                 = 'shiphero/general/enable';


    public function __construct(
        Context                    $context,
        ScopeConfigInterface       $scopeConfig,
        StoreManagerInterface      $storeManager


    ) {

        $this->scopeConfig      = $scopeConfig;
        $this->storeManager     = $storeManager;

        $this->configModule = $this->getConfig(strtolower($this->_getModuleName()));

        parent::__construct($context);
    }

    public function isEnable(){
        return $this->getConfig(self::ACTIVE_MODULE);
    }

    public function getEndpointOrder(){
        return $this->getConfig(self::ENDPOINT_ORDER);
    }

    public function getEndpointProduct(){
        return $this->getConfig(self::ENDPOINT_PRODUCT);
    }

    public function getWarehouseCode(){
        return $this->getConfig(self::WAREHOUSE_CODE);
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

}
