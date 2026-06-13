<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Cron;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use ECInternet\CustomerFeatures\Logger\Logger;
use ECInternet\CustomerFeatures\Model\Config;
use ECInternet\CustomerFeatures\Model\EmailSender;
use Exception;

/**
 * AccountActivationEmail Cron
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class AccountActivationEmail
{
    private const CONFIG_PATH_ACTIVATION_NOTICE_TEMPLATE = 'customer_features/account_activation/activation_notice_template';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var \ECInternet\CustomerFeatures\Logger\Logger
     */
    private $logger;

    /**
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

    /**
     * @var \ECInternet\CustomerFeatures\Model\EmailSender
     */
    private $emailSender;

    /**
     * AccountActivationEmail constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface                $customerRepository
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \ECInternet\CustomerFeatures\Logger\Logger                       $logger
     * @param \ECInternet\CustomerFeatures\Model\Config                        $config
     * @param \ECInternet\CustomerFeatures\Model\EmailSender                   $emailSender
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        Logger $logger,
        Config $config,
        EmailSender $emailSender,
    ) {
        $this->customerRepository        = $customerRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->logger                    = $logger;
        $this->config                    = $config;
        $this->emailSender               = $emailSender;
    }

    /**
     * Execute cron
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->log('execute()');

        if (!$this->config->isAccountActivationCronEnabled()) {
            $this->log('execute() - Account Activate cron disabled.');
            return $this;
        }

        $maxEmailCount = $this->config->getAccountActivationCronMaxEmails();
        $this->log('execute()', ['maxEmailsPerCronRun' => $maxEmailCount]);

        // Load unactivated customers.
        $customers = $this->getCronJobCustomers($maxEmailCount);

        /** @var \Magento\Customer\Model\Customer $customer */
        foreach ($customers as $customer) {
            $customerEmail = $customer->getEmail();

            try {
                /** @var \Magento\Customer\Api\Data\CustomerInterface $customerData */
                $customerData = $customer->getDataModel();

                $this->log("AccountActivationEmail() - Marking customer [$customerEmail] as cron email sent...");
                $this->markCustomerActivationEmailSent($customerData);

                $this->log("AccountActivationEmail() - Sending cron email to customer [$customerEmail]...");
                $this->sendAccountActivationNoticeEmail($customerData);
            } catch (Exception $e) {
                $this->log('AccountActivationEmail()', ['exception' => $e->getMessage()]);
            }
        }

        return $this;
    }

    /**
     * Get Customers who are not activated and haven't been sent activation email
     *
     * @param int $limit
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCronJobCustomers(int $limit)
    {
        return $this->customerCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(Config::ATTRIBUTE_CUSTOMER_IS_ACTIVATED, ['eq' => '0'])
            ->addAttributeToFilter(Config::ATTRIBUTE_CUSTOMER_ACTIVATION_EMAIL_SENT, ['eq' => '0'])
            ->setPageSize($limit)
            ->setCurPage(1)
            ->load();
    }

    /**
     * Set Customer Attribute 'ecinternet_cust_active_sent' to 1
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    private function markCustomerActivationEmailSent(
        CustomerInterface $customer
    ) {
        $this->log('markCustomerActivationEmailSent()', ['customerId' => $customer->getId()]);

        $customer->setCustomAttribute(Config::ATTRIBUTE_CUSTOMER_ACTIVATION_EMAIL_SENT, 1);
        $this->customerRepository->save($customer);
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

        $this->emailSender->sendEmail($customer, self::CONFIG_PATH_ACTIVATION_NOTICE_TEMPLATE);
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
        $this->logger->info('Cron/AccountActivationEmail - ' . $message, $extra);
    }
}
