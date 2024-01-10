<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model;

use Magento\Customer\Model\Registration;
use ECInternet\CustomerFeatures\Helper\Data;

/**
 * Plugin for Magento\Customer\Model\Registration
 */
class RegistrationPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $_helper;

    /**
     * RegistrationPlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Helper\Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * If Registration is not allowed by the plugin - returns false, otherwise returns original value.
     *
     * @param \Magento\Customer\Model\Registration $subject
     * @param bool                                 $result
     *
     * @return bool
     */
    public function afterIsAllowed(
        /** @noinspection PhpUnusedParameterInspection */ Registration $subject,
        bool $result
    ) {
        if ($this->_helper->isModuleEnabled()) {
            if ($this->_helper->disableCustomerRegistration()) {
                return false;
            }
        }

        return $result;
    }
}
