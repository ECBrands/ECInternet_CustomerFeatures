<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Test\Integration\Setup;

use Magento\Eav\Model\Config as EavConfig;
use Magento\TestFramework\Helper\Bootstrap;
use Exception;
use PHPUnit\Framework\TestCase;

class ExtensionInstallTest extends TestCase
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    protected function setUp(): void
    {
        $objectManager   = Bootstrap::getObjectManager();
        $this->eavConfig = $objectManager->get(EavConfig::class);
    }

    // -------------------------------------------------------------------------
    // Customer EAV attributes
    // -------------------------------------------------------------------------

    public function testCustomerAttributeEcinternetIsActiveWasCreatedCorrectly(): void
    {
        $attribute = $this->getAttribute('customer', 'ecinternet_is_active');
        if ($attribute === null) {
            $this->fail('Customer attribute "ecinternet_is_active" does not exist.');
        }

        $this->assertEquals('int', $attribute->getBackendType());
        $this->assertEquals('Is Active', $attribute->getStoreLabel());
        $this->assertEquals('boolean', $attribute->getFrontendInput());
        $this->assertEquals(0, $attribute->getIsRequired());
        $this->assertEquals(1, $attribute->getData('is_visible'));
        $this->assertEquals(0, $attribute->getIsUserDefined());
        $this->assertEquals(999, $attribute->getData('sort_order'));

        $usedInForms = $attribute->getUsedInForms();
        $this->assertCount(1, $usedInForms);
        $this->assertContains('adminhtml_customer', $usedInForms);
    }

    public function testCustomerAttributeEcinternetCustomerActivatedWasCreatedCorrectly(): void
    {
        $attribute = $this->getAttribute('customer', 'ecinternet_customer_activated');
        if ($attribute === null) {
            $this->fail('Customer attribute "ecinternet_customer_activated" does not exist.');
        }

        $this->assertEquals('int', $attribute->getBackendType());
        $this->assertEquals('Is Account Activated', $attribute->getStoreLabel());
        $this->assertEquals('boolean', $attribute->getFrontendInput());
        $this->assertEquals(0, $attribute->getIsRequired());
        $this->assertEquals(1, $attribute->getData('is_visible'));
        $this->assertEquals(1, $attribute->getIsUserDefined());
        $this->assertEquals(999, $attribute->getData('sort_order'));

        $usedInForms = $attribute->getUsedInForms();
        $this->assertCount(2, $usedInForms);
        $this->assertContains('adminhtml_customer', $usedInForms);
        $this->assertContains('adminhtml_checkout', $usedInForms);
    }

    public function testCustomerAttributeEcinternetCustActiveSentWasCreatedCorrectly(): void
    {
        $attribute = $this->getAttribute('customer', 'ecinternet_cust_active_sent');
        if ($attribute === null) {
            $this->fail('Customer attribute "ecinternet_cust_active_sent" does not exist.');
        }

        $this->assertEquals('int', $attribute->getBackendType());
        $this->assertEquals('Automatic Account Activation Email Sent', $attribute->getStoreLabel());
        $this->assertEquals('boolean', $attribute->getFrontendInput());
        $this->assertEquals(0, $attribute->getIsRequired());
        $this->assertEquals(1, $attribute->getData('is_visible'));
        $this->assertEquals(1, $attribute->getIsUserDefined());
        $this->assertEquals(999, $attribute->getData('sort_order'));

        $usedInForms = $attribute->getUsedInForms();
        $this->assertCount(2, $usedInForms);
        $this->assertContains('adminhtml_customer', $usedInForms);
        $this->assertContains('adminhtml_checkout', $usedInForms);
    }

    public function testCustomerAttributeEcinternetCompanyNameWasCreatedCorrectly(): void
    {
        $attribute = $this->getAttribute('customer', 'ecinternet_company_name');
        if ($attribute === null) {
            $this->fail('Customer attribute "ecinternet_company_name" does not exist.');
        }

        $this->assertEquals('varchar', $attribute->getBackendType());
        $this->assertEquals('Company Name', $attribute->getStoreLabel());
        $this->assertEquals('text', $attribute->getFrontendInput());
        $this->assertEquals(0, $attribute->getIsRequired());
        $this->assertEquals(1, $attribute->getData('is_visible'));
        $this->assertEquals(1, $attribute->getIsUserDefined());
        $this->assertEquals(999, $attribute->getData('sort_order'));

        $usedInForms = $attribute->getUsedInForms();
        $this->assertCount(5, $usedInForms);
        $this->assertContains('adminhtml_customer', $usedInForms);
        $this->assertContains('adminhtml_checkout', $usedInForms);
        $this->assertContains('checkout_register', $usedInForms);
        $this->assertContains('customer_account_create', $usedInForms);
        $this->assertContains('customer_account_edit', $usedInForms);
    }

    public function testLegacyCompanyNameAttributeWasRemoved(): void
    {
        $attribute = $this->getAttribute('customer', 'company_name');
        $this->assertNull($attribute, 'Legacy customer attribute "company_name" should have been removed.');
    }

    // -------------------------------------------------------------------------
    // Customer address EAV attributes
    // -------------------------------------------------------------------------

    public function testCustomerAddressAttributeContactNameWasCreatedCorrectly(): void
    {
        $attribute = $this->getAttribute('customer_address', 'contact_name');
        if ($attribute === null) {
            $this->fail('CustomerAddress attribute "contact_name" does not exist.');
        }

        $this->assertEquals('varchar', $attribute->getBackendType());
        $this->assertEquals('Contact Name', $attribute->getStoreLabel());
        $this->assertEquals('text', $attribute->getFrontendInput());
        $this->assertEquals(0, $attribute->getIsRequired());
        $this->assertEquals(1, $attribute->getData('is_visible'));
        $this->assertEquals(0, $attribute->getIsUserDefined());
        $this->assertEquals(999, $attribute->getData('sort_order'));

        $usedInForms = $attribute->getUsedInForms();
        $this->assertCount(3, $usedInForms);
        $this->assertContains('adminhtml_customer_address', $usedInForms);
        $this->assertContains('customer_address_edit', $usedInForms);
        $this->assertContains('customer_register_address', $usedInForms);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getAttribute(string $entityTypeCode, string $attributeCode)
    {
        try {
            if ($attribute = $this->eavConfig->getAttribute($entityTypeCode, $attributeCode)) {
                if ($attribute->getAttributeId()) {
                    return $attribute;
                }
            }
        } catch (Exception) {
        }

        return null;
    }
}
