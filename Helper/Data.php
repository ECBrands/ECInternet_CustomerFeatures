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
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use ECInternet\CustomerFeatures\Model\Config;
use Exception;

/**
 * Helper
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    private const CONFIG_PATH_FORGOT_EMAIL_IDENTITY = 'customer/password/forgot_email_identity';

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var \Magento\Customer\Helper\View
     */
    private $customerViewHelper;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

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
     * @var \ECInternet\CustomerFeatures\Model\Config
     */
    private $config;

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
     * @param \ECInternet\CustomerFeatures\Model\Config                     $config
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
        Config $config,
        SenderResolverInterface $senderResolver = null
    ) {
        parent::__construct($context);

        $this->groupRepository    = $groupRepository;
        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry   = $customerRegistry;
        $this->customerSession    = $customerSession;
        $this->transportBuilder  = $transportBuilder;
        $this->dataProcessor     = $dataProcessor;
        $this->storeManager      = $storeManager;
        $this->config             = $config;
        $this->senderResolver     = $senderResolver ?: ObjectManager::getInstance()->get(SenderResolverInterface::class); //FIXME: Done this way by core Magento 2 in Magento\Customer\Model\EmailNotification -- We should fix this our own way.
    }

    /**
     * Can the current Customer add addresses?
     *
     * @return bool
     */
    public function canCurrentCustomerAddAddresses()
    {
        if (!$this->config->isModuleEnabled()) {
            return true;
        }

        if (!$this->config->shouldLimitAddAddress()) {
            return true;
        }

        if ($allowedCustomerGroups = explode(',', $this->config->getLimitAddAddressGroups())) {
            if ($customerGroup = $this->getCustomerGroupCodeForLoggedInCustomer()) {
                return in_array($customerGroup, $allowedCustomerGroups);
            }
        }

        return true;
    }

    /**
     * Can the current Customer edit addresses?
     *
     * @return bool
     */
    public function canCurrentCustomerEditAddresses()
    {
        if (!$this->config->isModuleEnabled()) {
            return true;
        }
        if (!$this->config->shouldLimitEditAddress()) {
            return true;
        }

        if ($allowedCustomerGroups = explode(',', $this->config->getLimitEditAddressGroups())) {
            if ($customerGroup = $this->getCustomerGroupCodeForLoggedInCustomer()) {
                return in_array($customerGroup, $allowedCustomerGroups);
            }
        }

        return true;
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
                self::CONFIG_PATH_FORGOT_EMAIL_IDENTITY,
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
        int $storeId = null,
        string $email = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

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
     * Get the CustomerGroup code for the currently logged-in Customer
     *
     * @return string|null
     */
    private function getCustomerGroupCodeForLoggedInCustomer()
    {
        if ($this->customerSession->isLoggedIn()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            if ($customer = $this->customerSession->getCustomer()) {
                /** @var \Magento\Customer\Api\Data\GroupInterface $group */
                if ($group = $this->getCustomerGroupById($customer->getGroupId())) {
                    return $group->getCode();
                }
            }
        }

        return null;
    }

    private function getCustomerGroupById($customerGroupId)
    {
        try {
            return $this->groupRepository->getById($customerGroupId);
        } catch (Exception $e) {
            $this->log('getCustomerGroupById()', [
                'customerGroupId' => $customerGroupId,
                'exception'       => $e->getMessage()
            ]);
        }

        return null;
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
