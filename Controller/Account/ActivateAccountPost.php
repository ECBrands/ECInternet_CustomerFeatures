<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\Account\ForgotPasswordPost;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\ValidatorChain;
use ECInternet\CustomerFeatures\Helper\Data;
use ECInternet\CustomerFeatures\Logger\Logger;
use Exception;

/**
 * ActivateAccountPost Account controller
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ActivateAccountPost extends ForgotPasswordPost
{
    /**
     * @var \ECInternet\CustomerFeatures\Logger\Logger
     */
    private $logger;

    /**
     * ActivateAccountPost constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Customer\Model\Session                  $customerSession
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Escaper                       $escaper
     * @param \ECInternet\CustomerFeatures\Logger\Logger       $logger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        Logger $logger
    ) {
        parent::__construct($context, $customerSession, $customerAccountManagement, $escaper);

        $this->logger = $logger;
    }

    /**
     * Forgot customer password action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!ValidatorChain::is($email, EmailAddress::class)) {
                $this->session->setForgottenEmail($email);
                $this->messageManager->addErrorMessage(
                    __('The email address is incorrect. Verify the email address and try again.')
                );

                return $resultRedirect->setPath('*/*/activateaccount');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    Data::EMAIL_ACTIVATE_TEMPLATE
                );
            } catch (NoSuchEntityException $exception) {
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
                $this->log("NoSuchEntityException found - {$exception->getMessage()}.");
            } catch (SecurityViolationException $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());

                return $resultRedirect->setPath('*/*/activateaccount');
            } catch (Exception $exception) {
                $this->messageManager->addExceptionMessage(
                    $exception,
                    __("We're unable to send the account activation email.")
                );

                return $resultRedirect->setPath('*/*/activateaccount');
            }
            $this->messageManager->addSuccessMessage($this->getSuccessMessage($email));

            return $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addErrorMessage(__('Please enter your email.'));

            return $resultRedirect->setPath('*/*/activateaccount');
        }
    }

    /**
     * Retrieve success message
     *
     * @param string $email
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to activate it.',
            $this->escaper->escapeHtml($email)
        );
    }

    /**
     * Write to extension log
     *
     * @param string $message
     */
    private function log(string $message)
    {
        $this->logger->info('Controller/Account/ActivateAccountPost - ' . $message);
    }
}
