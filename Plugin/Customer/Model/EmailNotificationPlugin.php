<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotification;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\EmailNotification
 */
class EmailNotificationPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * EmailNotificationPlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
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
        if ($this->config->isModuleEnabled() && $this->config->shouldDisableCustomerWelcomeEmail()) {
            return;
        }

        $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
    }
}
