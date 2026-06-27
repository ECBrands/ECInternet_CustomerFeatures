<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Test\Unit\Plugin\Magento\Customer\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\City;
use ECInternet\CustomerFeatures\Model\Config;
use ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model\Validator\CityPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CityPluginTest extends TestCase
{
    /** @var Config|MockObject */
    private $config;

    /** @var City|MockObject */
    private $subject;

    /** @var Customer|MockObject */
    private $customer;

    /** @var CityPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->config   = $this->createMock(Config::class);
        $this->subject  = $this->createMock(City::class);
        $this->customer = $this->createMock(Customer::class);
        $this->plugin   = new CityPlugin($this->config);
    }

    public function testAroundIsValidReturnsTrueWhenBypassEnabled(): void
    {
        $this->config->method('shouldBypassCityValidation')->willReturn(true);

        $proceed = function (Customer $_customer) {
            $this->fail('$proceed should not be called when bypass is enabled');
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }

    public function testAroundIsValidCallsProceedWhenBypassDisabled(): void
    {
        $this->config->method('shouldBypassCityValidation')->willReturn(false);

        $proceedCalled = false;
        $proceed = function (Customer $_customer) use (&$proceedCalled) {
            $proceedCalled = true;
            return false;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($proceedCalled);
        $this->assertFalse($result);
    }

    public function testAroundIsValidForwardsProceedReturnValue(): void
    {
        $this->config->method('shouldBypassCityValidation')->willReturn(false);

        $proceed = function (Customer $_customer) {
            return true;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }
}
