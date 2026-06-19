<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{
    /**
     * @var \Magento\Customer\Helper\View
     */
    private $customerViewHelper;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

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
     * EmailSender constructor.
     *
     * @param \Magento\Customer\Helper\View                            $customerViewHelper
     * @param \Magento\Customer\Model\CustomerRegistry                 $customerRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface       $scopeConfig
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     * @param \Magento\Framework\Mail\Template\TransportBuilder        $transportBuilder
     * @param \Magento\Framework\Reflection\DataObjectProcessor        $dataProcessor
     * @param \Magento\Store\Model\StoreManagerInterface               $storeManager
     */
    public function __construct(
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        ScopeConfigInterface $scopeConfig,
        SenderResolverInterface $senderResolver,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        StoreManagerInterface $storeManager,
    ) {
        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry   = $customerRegistry;
        $this->scopeConfig        = $scopeConfig;
        $this->senderResolver     = $senderResolver;
        $this->transportBuilder   = $transportBuilder;
        $this->dataProcessor      = $dataProcessor;
        $this->storeManager       = $storeManager;
    }

    /**
     * Send email to Customer from 'customer/password/forgot_email_identity' template
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $template
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendEmail(
        CustomerInterface $customer,
        string $template
    ) {
        $this->log('sendEmail()', ['customerId' => $customer->getId(), 'template' => $template]);

        $storeId = $this->storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $customer->getStoreId();
        }

        if (!is_numeric($storeId)) {
            $this->log('sendEmail() - Non-numeric Store ID', ['storeId' => $storeId]);
            return;
        }

        $customerEmailData = $this->getFullCustomerObject($customer);
        $this->sendEmailTemplate(
            $customer,
            $template,
            Customer::XML_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
            (int)$storeId
        );
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
     * Send corresponding email template
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string                                       $template       configuration path of email template
     * @param string                                       $sender         configuration path of email identity
     * @param array                                        $templateParams
     * @param int|null                                     $storeId
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
        int $storeId = null
    ) {
        $templateId    = $this->scopeConfig->getValue($template, 'store', $storeId);
        $customerEmail = $customer->getEmail();
        $customerName  = $this->customerViewHelper->getCustomerName($customer);

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue($sender, 'store', $storeId),
            $storeId
        );

        $this->log('sendEmailTemplate()', [
            'template'   => $template,
            'sender'     => $sender,
            'from'       => $from,
            'templateId' => $templateId,
            'storeId'    => $storeId,
            'email'      => $customerEmail,
            'name'       => $customerName,
            'params'     => $templateParams
        ]);

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFromByScope($from)
            ->addTo($customerEmail, $customerName)
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
        //$this->logger->info('Model/EmailSender - ' . $message, $extra);
    }
}
