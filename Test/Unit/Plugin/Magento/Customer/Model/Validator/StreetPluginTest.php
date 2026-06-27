<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Test\Unit\Plugin\Magento\Customer\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\Street;
use ECInternet\CustomerFeatures\Model\Config;
use ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model\Validator\StreetPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StreetPluginTest extends TestCase
{
    /** @var Config|MockObject */
    private $config;

    /** @var Street|MockObject */
    private $subject;

    /** @var Customer|MockObject */
    private $customer;

    /** @var StreetPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->config   = $this->createMock(Config::class);
        $this->subject  = $this->createMock(Street::class);
        $this->customer = $this->createMock(Customer::class);
        $this->plugin   = new StreetPlugin($this->config);
    }

    public function testAroundIsValidReturnsTrueWhenBypassEnabled(): void
    {
        $this->config->method('shouldBypassStreetValidation')->willReturn(true);

        $proceed = function (Customer $_customer) {
            $this->fail('$proceed should not be called when bypass is enabled');
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }

    public function testAroundIsValidCallsProceedWhenBypassDisabled(): void
    {
        $this->config->method('shouldBypassStreetValidation')->willReturn(false);

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
        $this->config->method('shouldBypassStreetValidation')->willReturn(false);

        $proceed = function (Customer $_customer) {
            return true;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }
}
