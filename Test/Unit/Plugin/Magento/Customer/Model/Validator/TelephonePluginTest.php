<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Test\Unit\Plugin\Magento\Customer\Model\Validator;

use Magento\Customer\Model\Validator\Telephone;
use ECInternet\CustomerFeatures\Model\Config;
use ECInternet\CustomerFeatures\Plugin\Magento\Customer\Model\Validator\TelephonePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TelephonePluginTest extends TestCase
{
    /** @var Config|MockObject */
    private $config;

    /** @var Telephone|MockObject */
    private $subject;

    /** @var TelephonePlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->config  = $this->createMock(Config::class);
        $this->subject = $this->createMock(Telephone::class);
        $this->plugin  = new TelephonePlugin($this->config);
    }

    public function testAroundIsValidReturnsTrueWhenBypassEnabled(): void
    {
        $this->config->method('shouldBypassTelephoneValidation')->willReturn(true);

        $customer = new \stdClass();
        $proceed  = function ($customer) {
            $this->fail('$proceed should not be called when bypass is enabled');
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $customer);

        $this->assertTrue($result);
    }

    public function testAroundIsValidCallsProceedWhenBypassDisabled(): void
    {
        $this->config->method('shouldBypassTelephoneValidation')->willReturn(false);

        $customer      = new \stdClass();
        $proceedCalled = false;
        $proceed       = function ($c) use (&$proceedCalled) {
            $proceedCalled = true;
            return false;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $customer);

        $this->assertTrue($proceedCalled);
        $this->assertFalse($result);
    }

    public function testAroundIsValidForwardsProceedReturnValue(): void
    {
        $this->config->method('shouldBypassTelephoneValidation')->willReturn(false);

        $customer = new \stdClass();
        $proceed  = function ($c) {
            return true;
        };

        $result = $this->plugin->aroundIsValid($this->subject, $proceed, $customer);

        $this->assertTrue($result);
    }
}
