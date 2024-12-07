<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Plugin\Customer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use ECInternet\CustomerFeatures\Helper\Data;
use ECInternet\CustomerFeatures\Logger\Logger;
use Exception;

/**
 * Plugin for Magento\Customer\Model\AccountManagement
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AccountManagementPlugin
{
    const CONFIG_PATH_ACTIVATION_TEMPLATE = 'customer_features/account_activation/activate_account_template';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $helper;

    /**
     * @var \ECInternet\CustomerFeatures\Logger\Logger
     */
    private $logger;

    /**
     * AccountManagementPlugin constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Message\ManagerInterface       $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \ECInternet\CustomerFeatures\Helper\Data          $helper
     * @param \ECInternet\CustomerFeatures\Logger\Logger        $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Data $helper,
        Logger $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->messageManager     = $messageManager;
        $this->storeManager       = $storeManager;
        $this->helper             = $helper;
        $this->logger             = $logger;
    }

    /**
     * Disallow Customers from logging in if they have a false 'ecinternet_is_active' value.
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param callable                                  $proceed
     * @param string                                    $username
     * @param string                                    $password
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\InvalidEmailOrPasswordException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundAuthenticate(
        /** @noinspection PhpUnusedParameterInspection */ AccountManagement $subject,
        callable $proceed,
        /* @noinspection PhpMissingParamTypeInspection */ $username,
        /* @noinspection PhpMissingParamTypeInspection */ $password
    ) {
        $this->log('aroundAuthenticate()');

        if ($this->helper->shouldDisallowLoginIfInactive()) {
            try {
                $customer = $this->customerRepository->get($username);
            } catch (NoSuchEntityException) {
                throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
            }

            $isActive = $customer->getCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVE);
            if ($isActive !== null) {
                $isActiveValue = $isActive->getValue();
                if ($isActiveValue == 0) {
                    throw new UserLockedException(__('The account is locked.'));
                }
            }
        }

        // Call original method
        return $proceed($username, $password);
    }

    /**
     * Add error handling for failed password reset emails
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param callable                                  $proceed
     * @param string                                    $email
     * @param string                                    $template
     * @param int|null                                  $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function aroundInitiatePasswordReset(
        /** @noinspection PhpUnusedParameterInspection */ AccountManagement $subject,
        callable $proceed,
        /* @noinspection PhpMissingParamTypeInspection */ $email,
        /* @noinspection PhpMissingParamTypeInspection */ $template,
        /* @noinspection PhpMissingParamTypeInspection */ $websiteId = null
    ) {
        $this->log('aroundInitiatePasswordReset()', [
            'email'     => $email,
            'template'  => $template,
            'websiteId' => $websiteId
        ]);

        try {
            return $proceed($email, $template, $websiteId);
        } catch (InputException $e) {
            // InputException means handleUnknownTemplate was called before

            // Let's make sure this is the right template file for our needs and take over.
            if ($this->helper->isModuleEnabled() && $template == Data::EMAIL_ACTIVATE_TEMPLATE) {
                $this->log("aroundInitiatePasswordReset() - Caught InputException with template: [$template]: [{$e->getMessage()}].");

                // Load customer by email
                $customer = $this->customerRepository->get($email, $websiteId);
                try {
                    $this->sendAccountActivationConfirmationEmail($customer);

                    return true;
                } catch (MailException $e) {
                    $this->log("aroundInitiatePasswordReset() - MailException sending activation email: [{$e->getMessage()}].");

                    return false;
                } catch (Exception $e) {
                    $this->log("aroundInitiatePasswordReset() - Exception sending activation email: [{$e->getMessage()}].");
                    throw $e;
                }
            }

            throw $e;
        }
    }

    /**
     * Updates 'ecinternet_customer_activated' on the Customer
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param callable                                  $proceed
     * @param string                                    $email
     * @param string                                    $resetToken
     * @param string                                    $newPassword
     *
     * @return bool
     */
    public function aroundResetPassword(
        /** @noinspection PhpUnusedParameterInspection */ AccountManagement $subject,
        callable $proceed,
        /* @noinspection PhpMissingParamTypeInspection */ $email,
        /* @noinspection PhpMissingParamTypeInspection */ $resetToken,
        /* @noinspection PhpMissingParamTypeInspection */ $newPassword
    ) {
        $this->log('aroundResetPassword()', [
            'email'       => $email,
            'resetToken'  => $resetToken,
            'newPassword' => $newPassword
        ]);

        // Run base functionality
        $result = $proceed($email, $resetToken, $newPassword);
        $this->log('aroundResetPassword() - Result: ' . ($result ? 'true' : 'false'));

        if ($result === true) {
            $this->activateAccount($email);
        }

        return $result;
    }

    /**
     * Set custom password value for new Customers
     *
     * @param \Magento\Customer\Model\AccountManagement    $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $password
     * @param string                                       $redirectUrl
     *
     * @return array
     */
    public function beforeCreateAccount(
        /** @noinspection PhpUnusedParameterInspection */ AccountManagement $subject,
        CustomerInterface $customer,
        /* @noinspection PhpMissingParamTypeInspection */ $password = null,
        /* @noinspection PhpMissingParamTypeInspection */ $redirectUrl = ''
    ) {
        $this->log('beforeCreateAccount()');

        if ($this->helper->isModuleEnabled()) {
            if ($this->helper->getNewAccountPasswordOverride()) {
                if ($value = $this->helper->getNewAccountPassword()) {
                    $password = $value;
                }
            }
        }

        return [$customer, $password, $redirectUrl];
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
    private function sendAccountActivationConfirmationEmail(
        CustomerInterface $customer
    ) {
        $this->log('sendAccountActivationConfirmationEmail()');

        $this->helper->sendEmail($customer, self::CONFIG_PATH_ACTIVATION_TEMPLATE);
    }

    /**
     * @param string $email
     *
     * @return void
     */
    private function activateAccount(string $email)
    {
        $this->log('activateAccount()', ['email' => $email]);

        // Handle any activation
        if ($this->helper->isAccountActivationEnabled()) {
            try {
                // Get the customer by their email and mark them as activated
                if ($customer = $this->getCustomerByEmail($email)) {
                    /** @var \Magento\Framework\Api\AttributeInterface|null $isActivated */
                    $isActivated = $customer->getCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVATED);
                    if ($isActivated !== null) {
                        if (!$isActivated->getValue()) {
                            $customer->setCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVATED, 1);
                            $this->customerRepository->save($customer);
                            $this->log('activateAccount() - Customer saved.');

                            $this->messageManager->addSuccessMessage('Your account has been activated.');
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log('activateAccount()', ['exception' => $e->getMessage()]);
            }
        }
    }

    /**
     * Lookup Customer by email
     *
     * @param string $email
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerByEmail(string $email)
    {
        $this->log('getCustomerByEmail()', ['email' => $email]);

        return $this->customerRepository->get($email, $this->storeManager->getStore()->getWebsiteId());
    }

    /**
     * Write to extension log
     *
     * @param string $message
     * @param array  $extra
     *
     * @return void
     */
    private function log(string $message, array $extra = [])
    {
        $this->logger->info('Plugin/Customer/Model/AccountManagementPlugin - ' . $message, $extra);
    }
}
