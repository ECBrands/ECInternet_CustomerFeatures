<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const CONFIG_PATH_ENABLED = 'customer_features/general/enable';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is module enabled?
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }
}
