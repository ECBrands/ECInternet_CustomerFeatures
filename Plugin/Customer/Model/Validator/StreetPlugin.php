<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\Street;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\Validator\Street
 */
class StreetPlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * StreetPlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Bypass street validation when the setting is enabled.
     * (Address is sometimes passed in, so set type of $customer as mixed)
     *
     * @param Street   $subject
     * @param callable $proceed
     * @param mixed    $customer
     *
     * @return bool
     */
    public function aroundIsValid(Street $subject, callable $proceed, mixed $customer)
    {
        if ($this->config->shouldBypassStreetValidation()) {
            return true;
        }

        return $proceed($customer);
    }
}
