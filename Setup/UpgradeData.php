<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Data upgrade script
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    private $_customerSetupFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    private $_attributeSetFactory;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Customer\Setup\CustomerSetupFactory   $customerSetupFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->_customerSetupFactory = $customerSetupFactory;
        $this->_attributeSetFactory  = $attributeSetFactory;
    }

    /**
     * Upgrades DB for a module
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     *
     * @return void
     * @throws \Exception
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            /** @var \Magento\Eav\Model\Entity\Type $customerEntity */
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
            $attributeSet     = $this->_attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_is_active')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.1.3', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            /** @var \Magento\Eav\Model\Entity\Type $customerEntity */
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
            $attributeSet     = $this->_attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'company_name',
                [
                    'type'         => 'varchar',
                    'label'        => 'Company Name',
                    'input'        => 'text',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => true,
                    'position'     => 999,
                    'system'       => 0
                ]
            );

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'company_name')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms'      => [
                        'adminhtml_customer',
                        'checkout_register',
                        'customer_account_create',
                        'customer_account_edit',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            /** @var \Magento\Eav\Model\Entity\Type $customerEntity */
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
            $attributeSet     = $this->_attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'ecinternet_customer_activated',
                [
                    'type'         => 'int',
                    'label'        => 'Is Account Activated',
                    'input'        => 'boolean',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => true,
                    'position'     => 999,
                    'system'       => 0
                ]
            );

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_customer_activated')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms'      => [
                        'adminhtml_customer',
                        'checkout_register',
                        'customer_account_create',
                        'customer_account_edit',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'ecinternet_cust_active_sent',
                [
                    'type'         => 'int',
                    'label'        => 'Automatic Account Activation Email Sent',
                    'input'        => 'boolean',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => true,
                    'position'     => 999,
                    'system'       => 0
                ]
            );

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_cust_active_sent')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms'      => [
                        'adminhtml_customer',
                        'checkout_register',
                        'customer_account_create',
                        'customer_account_edit',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.3.4', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_customer_activated')
                ->addData([
                    'used_in_forms' => [
                        'adminhtml_customer',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_cust_active_sent')
                ->addData([
                    'used_in_forms' => [
                        'adminhtml_customer',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();
        }

        // 1.3.7 - Add 'ecinternet_company_name'
        if (version_compare($context->getVersion(), '1.3.7', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            /** @var \Magento\Eav\Model\Entity\Type $customerEntity */
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
            $attributeSet     = $this->_attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(
                Customer::ENTITY,
                'ecinternet_company_name',
                [
                    'type'         => 'varchar',
                    'label'        => 'Company Name',
                    'input'        => 'text',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => true,
                    'position'     => 999,
                    'system'       => 0
                ]
            );

            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute(Customer::ENTITY, 'ecinternet_company_name')
                ->addData([
                    'attribute_set_id'   => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
                    'used_in_forms'      => [
                        'adminhtml_customer',
                        'checkout_register',
                        'customer_account_create',
                        'customer_account_edit',
                        'adminhtml_checkout'
                    ]
                ]);

            /* @noinspection PhpDeprecationInspection */
            $attribute->save();
        }

        // 1.3.8 - Remove 'company_name'
        if (version_compare($context->getVersion(), '1.3.8', '<')) {
            /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
            $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

            $customerSetup->removeAttribute(Customer::ENTITY, 'company_name');
        }

        $installer->endSetup();
    }
}
