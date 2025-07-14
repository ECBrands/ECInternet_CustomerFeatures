<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * ViewModel for customer_account_index
 */
class CustomerAccountIndex implements ArgumentInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * CustomerAccountIndex constructor.
     *
     * @param \Magento\Customer\Model\Session           $customerSession
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        CustomerSession $customerSession,
        Config $config
    ) {
        $this->customerSession = $customerSession;
        $this->config          = $config;
    }

    /**
     * Should we should additional information in Customer title?
     *
     * @return bool
     */
    public function shouldShowAdditionalInformation()
    {
        return $this->config->shouldShowAdditionalInformation();
    }

    /**
     * Get the 'customer_number' value of the current Customer
     *
     * @return string
     */
    public function getCurrentCustomerNumber()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getData('customer_number');
        }

        return '';
    }

    /**
     * Get the 'ecinternet_company_name' value of the current Customer
     *
     * @return string
     */
    public function getCurrentCustomerCompanyName()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getData('ecinternet_company_name');
        }

        return '';
    }
}
