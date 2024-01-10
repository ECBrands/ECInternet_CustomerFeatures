<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Setup;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Data install script
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $_eavConfig;

    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * InstallData constructor.
     *
     * @param \Magento\Eav\Model\Config          $eavConfig
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_eavConfig       = $eavConfig;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Install data for a module
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     *
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            Customer::ENTITY,
            'ecinternet_is_active',
            [
                'type'         => 'int',
                'label'        => 'Is Active',
                'input'        => 'boolean',
                'source'       => Boolean::class,
                'required'     => false,
                'visible'      => true,
                'user_defined' => false,
                'position'     => 999,
                'system'       => 0,
                'default'      => '1',
            ]
        );

        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $customerActiveAttribute */
        $customerActiveAttribute = $this->_eavConfig->getAttribute(
            Customer::ENTITY,
            'ecinternet_is_active'
        );

        $customerActiveAttribute->setData(
            'used_in_forms',
            ['adminhtml_customer']
        );

        /* @noinspection PhpDeprecationInspection */
        $customerActiveAttribute->save();

        $eavSetup->addAttribute(
            'customer_address',
            'contact_name',
            [
                'type'             => 'varchar',
                'label'            => 'Contact Name',
                'input'            => 'text',
                'required'         => false,
                'visible'          => true,
                'visible_on_front' => false,
                'user_defined'     => false,
                'position'         => 999,
                'system'           => 0,
            ]
        );

        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $customerAddressContactNameAttribute */
        $customerAddressContactNameAttribute = $this->_eavConfig->getAttribute(
            'customer_address',
            'contact_name'
        );

        $customerAddressContactNameAttribute->setData(
            'used_in_forms',
            [
                'adminhtml_customer_address',
                'customer_address_edit',
                'customer_register_address',
            ]
        );

        /* @noinspection PhpDeprecationInspection */
        $customerAddressContactNameAttribute->save();
    }
}
