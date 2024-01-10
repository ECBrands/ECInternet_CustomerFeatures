<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use ECInternet\CustomerFeatures\Helper\Data;

/**
 * ViewModel for customer_account_index
 */
class CustomerAccountIndex implements ArgumentInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $_helper;

    /**
     * CustomerAccountIndex constructor.
     *
     * @param \Magento\Customer\Model\Session          $customerSession
     * @param \ECInternet\CustomerFeatures\Helper\Data $helper
     */
    public function __construct(
        CustomerSession $customerSession,
        Data $helper
    ) {
        $this->_customerSession = $customerSession;
        $this->_helper          = $helper;
    }

    /**
     * Should we should additional information in Customer title?
     *
     * @return bool
     */
    public function shouldShowAdditionalInformation()
    {
        return $this->_helper->shouldShowAdditionalInformation();
    }

    /**
     * Get the 'customer_number' value of the current Customer
     *
     * @return string
     */
    public function getCurrentCustomerNumber()
    {
        if ($this->_customerSession->isLoggedIn()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            if ($customer = $this->_customerSession->getCustomer()) {
                return $customer->getData('customer_number');
            }
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
        if ($this->_customerSession->isLoggedIn()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            if ($customer = $this->_customerSession->getCustomer()) {
                return $customer->getData('ecinternet_company_name');
            }
        }

        return '';
    }
}
