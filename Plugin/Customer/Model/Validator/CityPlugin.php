<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\City;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\Validator\City
 */
class CityPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Bypass city validation when the setting is enabled.
     * (Address is sometimes passed in, so set type of $customer as mixed)
     *
     * @param City     $subject
     * @param callable $proceed
     * @param Customer $customer
     *
     * @return bool
     */
    public function aroundIsValid(City $subject, callable $proceed, mixed $customer)
    {
        if ($this->config->shouldBypassCityValidation()) {
            return true;
        }

        return $proceed($customer);
    }
}
