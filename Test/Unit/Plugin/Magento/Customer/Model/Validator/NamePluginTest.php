<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Test\Unit\Plugin\Magento\Customer\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator\Name;
use ECInternet\CustomerFeatures\Model\Config;
use ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model\Validator\NamePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NamePluginTest extends TestCase
{
    /** @var Config|MockObject */
    private $config;

    /** @var Name|MockObject */
    private $subject;

    /** @var Customer|MockObject */
    private $customer;

    /** @var NamePlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->config   = $this->createMock(Config::class);
        $this->subject  = $this->createMock(Name::class);
        $this->customer = $this->createMock(Customer::class);
        $this->plugin   = new NamePlugin($this->config);
    }

    public function testAroundIsValidReturnsTrueWhenBypassEnabled(): void
    {
        $this->config->method('shouldBypassNameValidation')->willReturn(true);

        $proceed = function (Customer $_customer) {
            $this->fail('$proceed should not be called when bypass is enabled');
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }

    public function testAroundIsValidCallsProceedWhenBypassDisabled(): void
    {
        $this->config->method('shouldBypassNameValidation')->willReturn(false);

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
        $this->config->method('shouldBypassNameValidation')->willReturn(false);

        $proceed = function (Customer $_customer) {
            return true;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $this->customer);

        $this->assertTrue($result);
    }
}
