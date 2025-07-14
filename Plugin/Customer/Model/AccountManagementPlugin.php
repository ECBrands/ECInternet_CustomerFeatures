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
use ECInternet\CustomerFeatures\Model\Config;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Plugin for Magento\Customer\Model\AccountManagement
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AccountManagementPlugin
{
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
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AccountManagementPlugin constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Message\ManagerInterface       $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \ECInternet\CustomerFeatures\Helper\Data          $helper
     * @param \ECInternet\CustomerFeatures\Model\Config         $config
     * @param \Psr\Log\LoggerInterface                          $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Data $helper,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->messageManager     = $messageManager;
        $this->storeManager       = $storeManager;
        $this->helper             = $helper;
        $this->config             = $config;
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

        if ($this->config->shouldDisallowLoginIfInactive()) {
            try {
                $customer = $this->customerRepository->get($username);
            } catch (NoSuchEntityException) {
                throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
            }

            $isActive = $customer->getCustomAttribute(Config::ATTRIBUTE_CUSTOMER_IS_ACTIVE);
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
            if ($this->config->isModuleEnabled() && $template == Data::EMAIL_ACTIVATE_TEMPLATE) {
                $this->log("aroundInitiatePasswordReset() - Caught InputException with template: [$template]");

                // Load customer by email
                $customer = $this->customerRepository->get($email, $websiteId);
                try {
                    $this->helper->sendAccountActivationConfirmationEmail($customer);

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
        $this->log('aroundResetPassword()', ['email' => $email]);

        // Run base functionality
        $result = $proceed($email, $resetToken, $newPassword);

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

        if ($this->config->isModuleEnabled() && $this->config->shouldOverrideNewAccountPassword()) {
            if ($value = $this->config->getNewAccountPassword()) {
                $password = $value;
            }
        }

        return [$customer, $password, $redirectUrl];
    }

    /**
     * @param string $email
     *
     * @return void
     */
    private function activateAccount(string $email)
    {
        $this->log('activateAccount()', ['email' => $email]);

        if (!$this->config->isAccountActivationEnabled()) {
            $this->log('activateAccount() - Account activation is not enabled');
            return;
        }

        // Get the customer by their email and mark them as activated
        $customer = $this->getCustomerByEmail($email);
        if ($customer === null) {
            return;
        }

        /** @var \Magento\Framework\Api\AttributeInterface|null $isActivated */
        $isActivated = $customer->getCustomAttribute(Config::ATTRIBUTE_CUSTOMER_IS_ACTIVATED);
        if ($isActivated !== null) {
            if (!$isActivated->getValue()) {
                $customer->setCustomAttribute(Config::ATTRIBUTE_CUSTOMER_IS_ACTIVATED, 1);

                try {
                    $this->customerRepository->save($customer);
                    $this->messageManager->addSuccessMessage('Your account has been activated.');
                } catch (Exception $e) {
                    $this->log('activateAccount()', ['exception' => $e->getMessage()]);
                    $this->messageManager->addErrorMessage('Your account has not been activated.');
                }
            }
        }
    }

    /**
     * Lookup Customer by email
     *
     * @param string $email
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function getCustomerByEmail(string $email)
    {
        try {
            return $this->customerRepository->get($email, $this->storeManager->getStore()->getWebsiteId());
        } catch (Exception $e) {
            $this->log('getCustomerByEmail()', ['email' => $email, 'exception' => $e->getMessage()]);
        }

        return null;
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
