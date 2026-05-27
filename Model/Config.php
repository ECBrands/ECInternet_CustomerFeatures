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

    private const CONFIG_PATH_SHOW_ADDITIONAL_INFO          = 'customer_features/general/show_additional_info';

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

    private const CONFIG_PATH_BYPASS_CITY_VALIDATION        = 'customer_features/validator/bypass_city_validation';

    private const CONFIG_PATH_BYPASS_NAME_VALIDATION        = 'customer_features/validator/bypass_name_validation';

    private const CONFIG_PATH_BYPASS_STREET_VALIDATION      = 'customer_features/validator/bypass_street_validation';

    private const CONFIG_PATH_BYPASS_TELEPHONE_VALIDATION   = 'customer_features/validator/bypass_telephone_validation';

    public const ATTRIBUTE_CUSTOMER_COMPANY_NAME            = 'ecinternet_company_name';

    public const ATTRIBUTE_CUSTOMER_ACTIVATION_EMAIL_SENT   = 'ecinternet_cust_active_sent';

    public const ATTRIBUTE_CUSTOMER_IS_ACTIVATED            = 'ecinternet_customer_activated';

    public const ATTRIBUTE_CUSTOMER_IS_ACTIVE               = 'ecinternet_is_active';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

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
     * Should we should additional information in Customer title?
     *
     * @return bool
     */
    public function shouldShowAdditionalInformation()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_SHOW_ADDITIONAL_INFO);
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
        return (int)$this->scopeConfig->getValue(self::CONFIG_PATH_ACTIVATION_CRON_MAX_EMAILS);
    }

    /**
     * Should we disallow Customer registration?
     *
     * @return bool
     */
    public function disableCustomerRegistration()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_DISABLE_REGISTRATION);
    }

    /**
     * Should we disable Customer welcome email?
     *
     * @return bool
     */
    public function disableCustomerWelcomeEmail()
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
    public function limitAddAddressGroups()
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
    public function limitEditAddressGroups()
    {
        return (string)$this->scopeConfig->getValue(self::CONFIG_PATH_LIMIT_EDIT_CUSTOMER_GROUPS);
    }

    /**
     * Should we bypass city validation?
     *
     * @return bool
     */
    public function shouldBypassCityValidation()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_BYPASS_CITY_VALIDATION);
    }

    /**
     * Should we bypass name validation?
     *
     * @return bool
     */
    public function shouldBypassNameValidation()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_BYPASS_NAME_VALIDATION);
    }

    /**
     * Should we bypass street validation?
     *
     * @return bool
     */
    public function shouldBypassStreetValidation()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_BYPASS_STREET_VALIDATION);
    }

    /**
     * Should we bypass telephone validation?
     *
     * @return bool
     */
    public function shouldBypassTelephoneValidation()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_BYPASS_TELEPHONE_VALIDATION);
    }
}
