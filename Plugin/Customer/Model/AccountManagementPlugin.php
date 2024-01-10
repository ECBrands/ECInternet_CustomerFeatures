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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
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
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \ECInternet\CustomerFeatures\Helper\Data
     */
    private $_helper;

    /**
     * @var \ECInternet\CustomerFeatures\Logger\Logger
     */
    private $_logger;

    /**
     * AccountManagementPlugin constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder      $searchCriteriaBuilder
     * @param \Magento\Framework\Message\ManagerInterface       $messageManager
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \ECInternet\CustomerFeatures\Helper\Data          $helper
     * @param \ECInternet\CustomerFeatures\Logger\Logger        $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Data $helper,
        Logger $logger
    ) {
        $this->_customerRepository    = $customerRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_messageManager        = $messageManager;
        $this->_storeManager          = $storeManager;
        $this->_helper                = $helper;
        $this->_logger                = $logger;
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

        if ($this->_helper->shouldDisallowLoginIfInactive()) {
            try {
                $customer = $this->_customerRepository->get($username);
            } catch (NoSuchEntityException $e) {
                throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
            }

            $isActive = $customer->getCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVE);
            if ($isActive !== null) {
                $isActiveValue = $isActive->getValue();
                if ($isActiveValue == 0) {
                    // Note: text is not sent to screen
                    throw new InvalidEmailOrPasswordException(__('Access is locked'));
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
            'websiteId' => $websiteId,
        ]);

        try {
            return $proceed($email, $template, $websiteId);
        } catch (InputException $e) {
            // InputException means handleUnknownTemplate was called before

            // Let's make sure this is the right template file for our needs and take over.
            if ($this->_helper->isModuleEnabled() && $template == Data::EMAIL_ACTIVATE_TEMPLATE) {
                $this->log("aroundInitiatePasswordReset() - Caught InputException with template: [$template]");

                // Load customer by email
                $customer = $this->_customerRepository->get($email, $websiteId);
                try {
                    $this->_helper->sendAccountActivationConfirmationEmail($customer);

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

        try {
            $customerEmail = $email;
            if (!$customerEmail) {
                $customerByRp  = $this->matchCustomerByRpToken($resetToken);
                $customerEmail = $customerByRp->getEmail();
            }
        } catch (Exception $e) {
            $this->log("aroundResetPassword() - Exception occurred getting customer email: [{$e->getMessage()}].");
        }

        // Run base functionality
        $result = $proceed($email, $resetToken, $newPassword);

        if ($result === true && $this->_helper->isAccountActivationEnabled()) {
            $this->log('aroundResetPassword() - Success, and feature is enabled.');

            // Successful password reset, let's handle any applicable activation data
            try {
                // Get the customer by their email and mark them as activated
                if ($customer = $this->getCustomerByEmail($customerEmail)) {
                    /** @var \Magento\Framework\Api\AttributeInterface|null $isActivated */
                    $isActivated = $customer->getCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVATED);
                    if ($isActivated !== null) {
                        if (!$isActivated->getValue()) {
                            $customer->setCustomAttribute(Data::ATTRIBUTE_CUSTOMER_IS_ACTIVATED, 1);
                            $this->_customerRepository->save($customer);
                            $this->log('aroundResetPassword() - Customer saved.');

                            $this->_messageManager->addSuccessMessage('Your account has been activated.');
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log('aroundResetPassword()', ['exception' => $e->getMessage()]);
            }
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

        if ($this->_helper->isModuleEnabled()) {
            if ($this->_helper->getNewAccountPasswordOverride()) {
                if ($value = $this->_helper->getNewAccountPassword()) {
                    $password = $value;
                }
            }
        }

        return [$customer, $password, $redirectUrl];
    }

    /**
     * Match a customer by their RP token.
     *
     * @param string $rpToken
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\ExpiredException
     */
    private function matchCustomerByRpToken(string $rpToken): CustomerInterface
    {
        $this->_searchCriteriaBuilder->addFilter('rp_token', $rpToken);
        $this->_searchCriteriaBuilder->setPageSize(1);

        /** @var \Magento\Customer\Api\Data\CustomerSearchResultsInterface $found */
        $found = $this->_customerRepository->getList(
            $this->_searchCriteriaBuilder->create()
        );

        // Failed to generated unique RP token
        if ($found->getTotalCount() > 1) {
            throw new ExpiredException(
                new Phrase('Reset password token expired.')
            );
        }

        // Customer with such token not found.
        if ($found->getTotalCount() === 0) {
            throw NoSuchEntityException::singleField('rp_token', $rpToken);
        }

        // Unique customer found.
        return $found->getItems()[0];
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
        return $this->_customerRepository->get($email, $this->_storeManager->getStore()->getWebsiteId());
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
        $this->_logger->info('Plugin/Customer/Model/AccountManagementPlugin - ' . $message, $extra);
    }
}
