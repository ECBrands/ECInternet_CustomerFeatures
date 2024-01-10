<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotification;
use ECInternet\CustomerFeatures\Helper\Data;

/**
 * Plugin for Magento\Customer\Model\EmailNotification
 */
class EmailNotificationPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $_helper;

    /**
     * EmailNotificationPlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Helper\Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * Disable Customer Welcome email
     *
     * @param \Magento\Customer\Model\EmailNotification    $subject
     * @param callable                                     $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $type
     * @param string                                       $backUrl
     * @param int|null                                     $storeId
     * @param string                                       $sendemailStoreId
     *
     * @return void
     */
    public function aroundNewAccount(
        /* @noinspection PhpUnusedParameterInspection */ EmailNotification $subject,
        callable $proceed,
        CustomerInterface $customer,
        /* @noinspection PhpMissingParamTypeInspection */ $type = EmailNotification::NEW_ACCOUNT_EMAIL_REGISTERED,
        /* @noinspection PhpMissingParamTypeInspection */ $backUrl = '',
        /* @noinspection PhpMissingParamTypeInspection */ $storeId = null,
        /* @noinspection PhpMissingParamTypeInspection */ $sendemailStoreId = null
    ): void {
        if (!$this->_helper->isModuleEnabled() || !$this->_helper->disableCustomerWelcomeEmail()) {
            $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }
    }
}
