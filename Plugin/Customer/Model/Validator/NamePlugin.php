<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model\Validator;

use Magento\Customer\Model\Validator\Name;
use ECInternet\CustomerFeatures\Model\Config;

/**
 * Plugin for Magento\Customer\Model\Validator\Name
 */
class NamePlugin
{
    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * NamePlugin constructor.
     *
     * @param \ECInternet\CustomerFeatures\Model\Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Bypass name validation when the setting is enabled.
     * (Address is sometimes passed in, so set type of $customer as mixed)
     *
     * @param Name     $subject
     * @param callable $proceed
     * @param mixed    $customer
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsValid(Name $subject, callable $proceed, mixed $customer)
    {
        if ($this->config->shouldBypassNameValidation()) {
            return true;
        }

        return $proceed($customer);
    }
}
