<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
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
    private const CONFIG_PATH_ACTIVATION_TEMPLATE        = 'customer_features/account_activation/activate_account_template';

    private const CONFIG_PATH_ACTIVATION_NOTICE_TEMPLATE = 'customer_features/account_activation/activation_notice_template';

    private const CONFIG_PATH_FORGOT_EMAIL_IDENTITY      = 'customer/password/forgot_email_identity';

    public const  EMAIL_ACTIVATE_TEMPLATE                = 'email_activate';

    /**
     * @var \Magento\Customer\Helper\View
     */
    private $customerViewHelper;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                    $context
     * @param \Magento\Customer\Helper\View                            $customerViewHelper
     * @param \Magento\Customer\Model\CustomerRegistry                 $customerRegistry
     * @param \Magento\Framework\Mail\Template\TransportBuilder        $transportBuilder
     * @param \Magento\Framework\Reflection\DataObjectProcessor        $dataProcessor
     * @param \Magento\Store\Model\StoreManagerInterface               $storeManager
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     */
    public function __construct(
        Context $context,
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        StoreManagerInterface $storeManager,
        SenderResolverInterface $senderResolver
    ) {
        parent::__construct($context);

        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry   = $customerRegistry;
        $this->transportBuilder   = $transportBuilder;
        $this->dataProcessor      = $dataProcessor;
        $this->storeManager       = $storeManager;
        $this->senderResolver     = $senderResolver;
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
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData       = $this->dataProcessor->buildOutputDataArray($customer, CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));

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

        $storeId = $this->storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $customer->getStoreId();
        }

        $customerEmailData = $this->getFullCustomerObject($customer);
        $this->sendEmailTemplate(
            $customer,
            $template,
            self::CONFIG_PATH_FORGOT_EMAIL_IDENTITY,
            $storeId,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)]
        );
    }

    /**
     * Send corresponding email template
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $template  configuration path of email template
     * @param string                                       $sender    configuration path of email identity
     * @param int                                          $storeId
     * @param array                                        $templateParams
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    private function sendEmailTemplate(
        CustomerInterface $customer,
        string $template,
        string $sender,
        int $storeId,
        array $templateParams = []
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        $email = $customer->getEmail();

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue($sender, 'store', $storeId),
            $storeId
        );

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFromByScope($from)
            ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $transport->sendMessage();
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
