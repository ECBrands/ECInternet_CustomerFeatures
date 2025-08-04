<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
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
     * @param \Magento\Framework\App\Helper\Context                         $context
     * @param \Magento\Customer\Helper\View                                 $customerViewHelper
     * @param \Magento\Customer\Model\CustomerRegistry                      $customerRegistry
     * @param \Magento\Framework\Mail\Template\TransportBuilder             $transportBuilder
     * @param \Magento\Framework\Reflection\DataObjectProcessor             $dataProcessor
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface|null $senderResolver
     */
    public function __construct(
        Context $context,
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        StoreManagerInterface $storeManager,
        SenderResolverInterface $senderResolver = null
    ) {
        parent::__construct($context);

        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry   = $customerRegistry;
        $this->transportBuilder   = $transportBuilder;
        $this->dataProcessor      = $dataProcessor;
        $this->storeManager       = $storeManager;
        $this->senderResolver     = $senderResolver ?: ObjectManager::getInstance()->get(SenderResolverInterface::class); //FIXME: Done this way by core Magento 2 in Magento\Customer\Model\EmailNotification -- We should fix this our own way.
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

        if (is_numeric($storeId)) {
            $customerEmailData = $this->getFullCustomerObject($customer);
            $this->sendEmailTemplate(
                $customer,
                $template,
                Customer::XML_PATH_FORGOT_EMAIL_IDENTITY,
                ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
                (int)$storeId
            );
        } else {
            $this->log('sendEmail() - Non-numeric Store ID', ['storeId' => $storeId]);
        }
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
        $this->_logger->info('Helper/Data - ' . $message, $extra);
    }
}
