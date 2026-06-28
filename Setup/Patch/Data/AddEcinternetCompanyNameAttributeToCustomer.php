<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class AddEcinternetCompanyNameAttributeToCustomer implements DataPatchInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute
     */
    private $attributeResource;

    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $setup;

    public function __construct(
        AttributeResource $attributeResource,
        CustomerSetupFactory $customerSetupFactory,
        ModuleDataSetupInterface $setup
    ) {
        $this->attributeResource    = $attributeResource;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->setup                = $setup;
    }

    public static function getDependencies(): array
    {
        return [AddEcinternetCustActiveSentAttributeToCustomer::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(): void
    {
        $this->setup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerSetup->addAttribute(Customer::ENTITY, 'ecinternet_company_name', [
            'type'         => 'varchar',
            'label'        => 'Company Name',
            'input'        => 'text',
            'required'     => false,
            'visible'      => true,
            'user_defined' => true,
            'position'     => 999,
            'system'       => 0,
        ]);

        if ($attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'ecinternet_company_name')) {
            $attribute->setData('used_in_forms', [
                'adminhtml_customer',
                'checkout_register',
                'customer_account_create',
                'customer_account_edit',
                'adminhtml_checkout',
            ]);
            $this->attributeResource->save($attribute);
        }

        $this->setup->getConnection()->endSetup();
    }
}
