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
use ECInternet\CustomerFeatures\Helper\Data;

/**
 * ViewModel for customer_account_index
 */
class CustomerAccountLogin implements ArgumentInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var \Magento\Framework\Url
     */
    private $_urlHelper;

    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $_helper;

    /**
     * CustomerAccountIndex constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Url                             $urlHelper
     * @param \ECInternet\CustomerFeatures\Helper\Data           $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Url $urlHelper,
        Data $helper
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_urlHelper   = $urlHelper;
        $this->_helper      = $helper;
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
        return $this->_helper->isAccountActivationEnabled();
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
        return $this->_urlHelper->getUrl(Data::CONFIG_PATH_ACTIVATE_ACCOUNT);
    }

    /**
     * Get the Store name
     *
     * @return string
     */
    private function getStoreName()
    {
        return $this->_scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
    }
}
