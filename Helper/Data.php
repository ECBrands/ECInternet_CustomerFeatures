<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Helper
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const CONFIG_PATH_ENABLED                       = 'general/enable';

    const CONFIG_PATH_DISALLOW_LOGIN_IF_INACTIVE    = 'general/disallow_login_if_inactive';

    const CONFIG_PATH_SHOW_ADDITIONAL_INFO          = 'general/show_additional_info';

    const CONFIG_PATH_DISABLE_REGISTRATION          = 'new_account/disable_customer_registration';

    const CONFIG_PATH_DISABLE_WELCOME_EMAIL         = 'new_account/disable_customer_welcome_email';

    const CONFIG_PATH_NEW_ACCOUNT_PASSWORD_OVERRIDE = 'new_account/enable_new_account_password_override';

    const CONFIG_PATH_NEW_ACCOUNT_PASSWORD          = 'new_account/new_account_password';

    const CONFIG_PATH_CUSTOMER_GROUP_LIMIT_ADD      = 'customer_group/limit_add_address';

    const CONFIG_PATH_LIMIT_ADD_CUSTOMER_GROUPS     = 'customer_group/limit_add_address_groups';

    const CONFIG_PATH_CUSTOMER_GROUP_LIMIT_EDIT     = 'customer_group/limit_edit_address';

    const CONFIG_PATH_LIMIT_EDIT_CUSTOMER_GROUPS    = 'customer_group/limit_edit_address_groups';

    const CONFIG_PATH_ACTIVATION_ENABLE             = 'account_activation/enable';

    const CONFIG_PATH_ACTIVATION_TEMPLATE           = 'customer_features/account_activation/activate_account_template';

    const CONFIG_PATH_ACTIVATION_ENABLE_CRON        = 'account_activation/enable_cron';

    const CONFIG_PATH_ACTIVATION_NOTICE_TEMPLATE    = 'customer_features/account_activation/activation_notice_template';

    const CONFIG_PATH_ACTIVATION_CRON_MAX_EMAILS    = 'account_activation/cron_customers_per';

    const CONFIG_PATH_FORGOT_EMAIL_IDENTITY         = 'customer/password/forgot_email_identity';

    const CONFIG_PATH_ACTIVATE_ACCOUNT              = 'customer/account/activateaccount';

    const EMAIL_ACTIVATE_TEMPLATE                   = 'email_activate';

    const ATTRIBUTE_CUSTOMER_COMPANY_NAME           = 'ecinternet_company_name';

    const ATTRIBUTE_CUSTOMER_ACTIVATION_EMAIL_SENT  = 'ecinternet_cust_active_sent';

    const ATTRIBUTE_CUSTOMER_IS_ACTIVATED           = 'ecinternet_customer_activated';

    const ATTRIBUTE_CUSTOMER_IS_ACTIVE              = 'ecinternet_is_active';

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $_groupRepository;

    /**
     * @var \Magento\Customer\Helper\View
     */
    private $_customerViewHelper;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $_customerRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    private $_senderResolver;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $_transportBuilder;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    private $_dataProcessor;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                         $context
     * @param \Magento\Customer\Api\GroupRepositoryInterface                $groupRepository
     * @param \Magento\Customer\Helper\View                                 $customerViewHelper
     * @param \Magento\Customer\Model\CustomerRegistry                      $customerRegistry
     * @param \Magento\Customer\Model\Session                               $customerSession
     * @param \Magento\Framework\Mail\Template\TransportBuilder             $transportBuilder
     * @param \Magento\Framework\Reflection\DataObjectProcessor             $dataProcessor
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface|null $senderResolver
     */
    public function __construct(
        Context $context,
        GroupRepositoryInterface $groupRepository,
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        CustomerSession $customerSession,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        StoreManagerInterface $storeManager,
        SenderResolverInterface $senderResolver = null
    ) {
        parent::__construct($context);

        $this->_groupRepository       = $groupRepository;
        $this->_customerViewHelper    = $customerViewHelper;
        $this->_customerRegistry      = $customerRegistry;
        $this->_customerSession       = $customerSession;
        $this->_transportBuilder      = $transportBuilder;
        $this->_dataProcessor         = $dataProcessor;
        $this->_storeManager          = $storeManager;
        $this->_senderResolver        = $senderResolver ?: ObjectManager::getInstance()->get(SenderResolverInterface::class); //FIXME: Done this way by core Magento 2 in Magento\Customer\Model\EmailNotification -- We should fix this our own way.
    }

    /**
     * Is module enabled?
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return $this->isSetFlag(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Should we disallow login if inactive?
     *
     * @return bool
     */
    public function shouldDisallowLoginIfInactive()
    {
        if ($this->isModuleEnabled()) {
            return $this->isSetFlag(self::CONFIG_PATH_DISALLOW_LOGIN_IF_INACTIVE);
        }

        return false;
    }

    /**
     * Should we should additional information in Customer title?
     *
     * @return bool
     */
    public function shouldShowAdditionalInformation()
    {
        if ($this->isModuleEnabled()) {
            return $this->isSetFlag(self::CONFIG_PATH_SHOW_ADDITIONAL_INFO);
        }

        return false;
    }

    /**
     * Is account activation enabled?
     *
     * @return bool
     */
    public function isAccountActivationEnabled()
    {
        if ($this->isModuleEnabled()) {
            return $this->isSetFlag(self::CONFIG_PATH_ACTIVATION_ENABLE);
        }

        return false;
    }

    /**
     * Is account activation cron enabled?
     *
     * @return bool
     */
    public function isAccountActivationCronEnabled()
    {
        if ($this->isAccountActivationEnabled()) {
            return $this->isSetFlag(self::CONFIG_PATH_ACTIVATION_ENABLE_CRON);
        }

        return false;
    }

    /**
     * Get the account activation cron max email limit
     *
     * @return int
     */
    public function getAccountActivationCronMaxEmails()
    {
        $maxEmails = $this->getValue(self::CONFIG_PATH_ACTIVATION_CRON_MAX_EMAILS);

        if (is_numeric($maxEmails)) {
            return (int)$maxEmails;
        }

        return 10; //Safe Default
    }

    /**
     * Should we disallow Customer registration?
     *
     * @return bool
     */
    public function disableCustomerRegistration()
    {
        return $this->isSetFlag(self::CONFIG_PATH_DISABLE_REGISTRATION);
    }

    /**
     * Should we disable Customer welcome email?
     *
     * @return bool
     */
    public function disableCustomerWelcomeEmail()
    {
        return $this->isSetFlag(self::CONFIG_PATH_DISABLE_WELCOME_EMAIL);
    }

    /**
     * Should we override new account passwords?
     *
     * @return bool
     */
    public function getNewAccountPasswordOverride()
    {
        return $this->isSetFlag(self::CONFIG_PATH_NEW_ACCOUNT_PASSWORD_OVERRIDE);
    }

    /**
     * Get the new account password
     *
     * @return string
     */
    public function getNewAccountPassword()
    {
        return (string)$this->getValue(self::CONFIG_PATH_NEW_ACCOUNT_PASSWORD);
    }

    /**
     * Should we limit the ability to add addresses by CustomerGroup?
     *
     * @return bool
     */
    public function shouldLimitAddAddress()
    {
        return $this->isSetFlag(self::CONFIG_PATH_CUSTOMER_GROUP_LIMIT_ADD);
    }

    /**
     * Get list of CustomerGroups for which to limit adding addresses
     *
     * @return string
     */
    public function limitAddAddressGroups()
    {
        return (string)$this->getValue(self::CONFIG_PATH_LIMIT_ADD_CUSTOMER_GROUPS);
    }

    /**
     * Should we limit the ability to edit addresses by CustomerGroup?
     *
     * @return bool
     */
    public function shouldLimitEditAddress()
    {
        return $this->isSetFlag(self::CONFIG_PATH_CUSTOMER_GROUP_LIMIT_EDIT);
    }

    /**
     * Get list of CustomerGroups for which to limit editing addresses
     *
     * @return string
     */
    public function limitEditAddressGroups()
    {
        return (string)$this->getValue(self::CONFIG_PATH_LIMIT_EDIT_CUSTOMER_GROUPS);
    }

    /**
     * Can the current Customer add addresses?
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function customerCanAddAddresses()
    {
        if ($this->isModuleEnabled()) {
            if ($this->shouldLimitAddAddress()) {
                if ($allowedCustomerGroups = explode(',', $this->limitAddAddressGroups())) {
                    if ($customerGroup = $this->getCustomerGroupCodeForLoggedInCustomer()) {
                        return in_array($customerGroup, $allowedCustomerGroups);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Can the current Customer edit addresses?
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function customerCanEditAddresses()
    {
        if ($this->isModuleEnabled()) {
            if ($this->shouldLimitEditAddress()) {
                if ($allowedCustomerGroups = explode(',', $this->limitEditAddressGroups())) {
                    if ($customerGroup = $this->getCustomerGroupCodeForLoggedInCustomer()) {
                        return in_array($customerGroup, $allowedCustomerGroups);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Create an object with data merged from Customer and CustomerSecure
     *
     * @param CustomerInterface $customer
     *
     * @return \Magento\Customer\Model\Data\CustomerSecure
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getFullCustomerObject(
        CustomerInterface $customer
    ) {
        // No need to flatten the custom attributes or nested objects.
        // The only usage is for email templates and objects passed for events.
        $mergedCustomerData = $this->_customerRegistry->retrieveSecureData($customer->getId());
        $customerData       = $this->_dataProcessor->buildOutputDataArray($customer, CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->_customerViewHelper->getCustomerName($customer));

        return $mergedCustomerData;
    }

    /**
     * Send email with reset password confirmation link
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendAccountActivationConfirmationEmail(
        CustomerInterface $customer
    ) {
        $this->log('sendAccountActivationConfirmationEmail()');

        $this->sendEmail($customer, self::CONFIG_PATH_ACTIVATION_TEMPLATE);
    }

    /**
     * Send email notifying customer they need to activate their account
     *
     * @param CustomerInterface $customer
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    public function sendAccountActivationNoticeEmail(
        CustomerInterface $customer
    ) {
        $this->log('sendAccountActivationNoticeEmail()');

        $this->sendEmail($customer, self::CONFIG_PATH_ACTIVATION_NOTICE_TEMPLATE);
    }

    /**
     * Send email to Customer
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $template
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendEmail(
        CustomerInterface $customer,
        string $template
    ) {
        $this->log('sendEmail()', ['customerId' => $customer->getId(), 'template' => $template]);

        $storeId = $this->_storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $customer->getStoreId();
        }

        $customerEmailData = $this->getFullCustomerObject($customer);
        $this->sendEmailTemplate(
            $customer,
            $template,
            self::CONFIG_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->_storeManager->getStore($storeId)],
            $storeId
        );
    }

    /**
     * Send corresponding email template
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $template       configuration path of email template
     * @param string                                       $sender         configuration path of email identity
     * @param array                                        $templateParams
     * @param int|null                                     $storeId
     * @param string|null                                  $email
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    private function sendEmailTemplate(
        CustomerInterface $customer,
        string $template,
        string $sender,
        array $templateParams = [],
        int $storeId = null,
        string $email = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        /** @var array $from */
        $from = $this->_senderResolver->resolve(
            $this->scopeConfig->getValue($sender, 'store', $storeId),
            $storeId
        );

        $transport = $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFromByScope($from)
            ->addTo($email, $this->_customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $transport->sendMessage();
    }

    /**
     * Get the CustomerGroup code for the currently logged-in Customer
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerGroupCodeForLoggedInCustomer()
    {
        if ($this->_customerSession->isLoggedIn()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            if ($customer = $this->_customerSession->getCustomer()) {
                /** @var \Magento\Customer\Api\Data\GroupInterface $group */
                if ($group = $this->_groupRepository->getById($customer->getGroupId())) {
                    return $group->getCode();
                }
            }
        }

        return null;
    }

    /**
     * Retrieve config value
     *
     * @param string $path
     *
     * @return mixed
     */
    private function getValue(string $path)
    {
        return $this->scopeConfig->getValue('customer_features/' . $path);
    }

    /**
     * Retrieve config flag by path
     *
     * @param string $path
     *
     * @return bool
     */
    private function isSetFlag(string $path)
    {
        return $this->scopeConfig->isSetFlag('customer_features/' . $path);
    }

    /**
     * Write to extension log
     *
     * @param string $message
     * @param array  $extra
     */
    private function log(string $message, array $extra = [])
    {
        $this->_logger->info('Helper/Data - ' . $message, $extra);
    }
}
