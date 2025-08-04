<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private const CONFIG_PATH_ENABLED                       = 'customer_features/general/enable';

    private const CONFIG_PATH_DISALLOW_LOGIN_IF_INACTIVE    = 'customer_features/general/disallow_login_if_inactive';

    private const CONFIG_PATH_DISABLE_REGISTRATION          = 'customer_features/new_account/disable_customer_registration';

    private const CONFIG_PATH_DISABLE_WELCOME_EMAIL         = 'customer_features/new_account/disable_customer_welcome_email';

    private const CONFIG_PATH_NEW_ACCOUNT_PASSWORD_OVERRIDE = 'customer_features/new_account/enable_new_account_password_override';

    private const CONFIG_PATH_NEW_ACCOUNT_PASSWORD          = 'customer_features/new_account/new_account_password';

    private const CONFIG_PATH_CUSTOMER_GROUP_LIMIT_ADD      = 'customer_features/customer_group/limit_add_address';

    private const CONFIG_PATH_LIMIT_ADD_CUSTOMER_GROUPS     = 'customer_features/customer_group/limit_add_address_groups';

    private const CONFIG_PATH_CUSTOMER_GROUP_LIMIT_EDIT     = 'customer_features/customer_group/limit_edit_address';

    private const CONFIG_PATH_LIMIT_EDIT_CUSTOMER_GROUPS    = 'customer_features/customer_group/limit_edit_address_groups';

    private const CONFIG_PATH_ACTIVATION_ENABLE             = 'customer_features/account_activation/enable';

    private const CONFIG_PATH_ACTIVATION_ENABLE_CRON        = 'customer_features/account_activation/enable_cron';

    private const CONFIG_PATH_ACTIVATION_CRON_MAX_EMAILS    = 'customer_features/account_activation/cron_customers_per';

    public const ATTRIBUTE_CUSTOMER_COMPANY_NAME            = 'ecinternet_company_name';

    public const ATTRIBUTE_CUSTOMER_ACTIVATION_EMAIL_SENT   = 'ecinternet_cust_active_sent';

    public const ATTRIBUTE_CUSTOMER_IS_ACTIVATED            = 'ecinternet_customer_activated';

    public const ATTRIBUTE_CUSTOMER_IS_ACTIVE               = 'ecinternet_is_active';

    public const EMAIL_ACTIVATION_TEMPLATE                  = 'email_activate';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is module enabled?
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Should we disallow login if Customer is inactive?
     *
     * @return bool
     */
    public function shouldDisallowLoginIfInactive()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_DISALLOW_LOGIN_IF_INACTIVE);
    }

    /**
     * Should we disallow Customer registration?
     *
     * @return bool
     */
    public function shouldDisableCustomerRegistration()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_DISABLE_REGISTRATION);
    }

    /**
     * Should we disable Customer welcome email?
     *
     * @return bool
     */
    public function shouldDisableCustomerWelcomeEmail()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_DISABLE_WELCOME_EMAIL);
    }

    /**
     * Should we override new account passwords?
     *
     * @return bool
     */
    public function shouldOverrideNewAccountPassword()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_NEW_ACCOUNT_PASSWORD_OVERRIDE);
    }

    /**
     * Get the new account password
     *
     * @return string
     */
    public function getNewAccountPassword()
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_NEW_ACCOUNT_PASSWORD);
    }

    /**
     * Should we limit the ability to add addresses by CustomerGroup?
     *
     * @return bool
     */
    public function shouldLimitAddAddress()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_CUSTOMER_GROUP_LIMIT_ADD);
    }

    /**
     * Get list of CustomerGroups for which to limit adding addresses
     *
     * @return string
     */
    public function getLimitAddAddressGroups()
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_LIMIT_ADD_CUSTOMER_GROUPS);
    }

    /**
     * Should we limit the ability to edit addresses by CustomerGroup?
     *
     * @return bool
     */
    public function shouldLimitEditAddress()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_CUSTOMER_GROUP_LIMIT_EDIT);
    }

    /**
     * Get list of CustomerGroups for which to limit editing addresses
     *
     * @return string
     */
    public function getLimitEditAddressGroups()
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_LIMIT_EDIT_CUSTOMER_GROUPS);
    }

    /**
     * Is account activation enabled?
     *
     * @return bool
     */
    public function isAccountActivationEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ACTIVATION_ENABLE);
    }

    /**
     * Is account activation cron enabled?
     *
     * @return bool
     */
    public function isAccountActivationCronEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ACTIVATION_ENABLE_CRON);
    }

    /**
     * Get the account activation cron max email limit
     *
     * @return int
     */
    public function getAccountActivationCronMaxEmails()
    {
        $maxEmails = $this->scopeConfig->getValue(self::CONFIG_PATH_ACTIVATION_CRON_MAX_EMAILS);

        if (is_numeric($maxEmails)) {
            return (int)$maxEmails;
        }

        return 10; //Safe Default
    }
}
