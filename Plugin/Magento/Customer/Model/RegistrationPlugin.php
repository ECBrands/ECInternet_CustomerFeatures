<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model;

use Magento\Customer\Model\Registration;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\Registration
 */
class RegistrationPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * RegistrationPlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * If Registration is not allowed by the plugin - returns false, otherwise returns original value.
     *
     * @param \Magento\Customer\Model\Registration $subject
     * @param bool                                 $result
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowed(
        Registration $subject,
        bool $result
    ) {
        if ($this->config->isModuleEnabled() && $this->config->shouldDisableCustomerRegistration()) {
            return false;
        }

        return $result;
    }
}
