<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Url;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * ViewModel for customer_account_index
 */
class CustomerAccountLogin implements ArgumentInterface
{
    private const CONFIG_PATH_ACTIVATE_ACCOUNT = 'customer/account/activateaccount';

    private const CONFIG_PATH_STORE_NAME       = 'general/store_information/name';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Url
     */
    private $urlHelper;

    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * CustomerAccountIndex constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Url                             $urlHelper
     * @param \ECInternet\CustomerFeatures\Model\Config          $config
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Url $urlHelper,
        Config $config
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlHelper   = $urlHelper;
        $this->config      = $config;
    }

    /**
     * Get CreateAccount note
     *
     * @return string
     */
    public function getCreateAccountNote()
    {
        $notes = 'Creating an account has many benefits: ';
        $notes .= 'check out faster, keep more than one address, track orders and more.';

        return $notes;
    }

    /**
     * Is account activation enabled?
     *
     * @return bool
     */
    public function isAccountActivationEnabled()
    {
        return $this->config->isAccountActivationEnabled();
    }

    /**
     * Get Activation note
     *
     * @return string
     */
    public function getActivationNote()
    {
        return "Activate your existing {$this->getStoreName()} account for store access.";
    }

    /**
     * Get Activation url (customer/account/activateaccount)
     *
     * @return string
     */
    public function getActivateAccountUrl()
    {
        return $this->urlHelper->getUrl(self::CONFIG_PATH_ACTIVATE_ACCOUNT);
    }

    /**
     * Get the Store name
     *
     * @return string
     */
    private function getStoreName()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_STORE_NAME, ScopeInterface::SCOPE_STORE);
    }
}
