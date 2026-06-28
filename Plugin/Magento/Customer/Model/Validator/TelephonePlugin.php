<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model\Validator;

use Magento\Customer\Model\Validator\Telephone;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\Validator\Telephone
 */
class TelephonePlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * TelephonePlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Bypass telephone validation when the setting is enabled.
     * (Address is sometimes passed in, so set type of $customer as mixed)
     *
     * @param Telephone $subject
     * @param callable  $proceed
     * @param mixed     $customer
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsValid(Telephone $subject, callable $proceed, mixed $customer)
    {
        if ($this->config->shouldBypassTelephoneValidation()) {
            return true;
        }

        return $proceed($customer);
    }
}
