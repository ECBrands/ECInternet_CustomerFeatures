<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template\Context;
use Exception;

class ECInternetCompanyName extends AbstractWidget
{
    private const ATTRIBUTE_CODE = 'ecinternet_company_name';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * ECInternetCompanyName constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param \Magento\Customer\Helper\Address                  $addressHelper
     * @param \Magento\Customer\Api\CustomerMetadataInterface   $customerMetadata
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session                   $customerSession
     * @param array                                             $data
     */
    public function __construct(
        Context $context,
        AddressHelper $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession    = $customerSession;

        parent::__construct($context, $addressHelper, $customerMetadata, $data);
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ECInternet_CustomerFeatures::widget/ecinternet_company_name.phtml');
    }

    /**
     * Check if 'ecinternet_company_name' attribute is enabled in system
     *
     * @return bool
     */
    public function isEnabled()
    {
        $attributeMetadata = $this->_getAttribute(self::ATTRIBUTE_CODE);

        return $attributeMetadata && $attributeMetadata->isVisible();
    }

    /**
     * Check if 'ecinternet_company_name' attribute is marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        $attributeMetadata = $this->_getAttribute(self::ATTRIBUTE_CODE);

        return $attributeMetadata && $attributeMetadata->isRequired();
    }

    /**
     * Get value of 'ecinternet_company_name' Customer attribute
     *
     * @return string
     */
    public function getECInternetCompanyName()
    {
        try {
            $customer = $this->getCustomer();
            if ($companyName = $customer->getCustomAttribute(self::ATTRIBUTE_CODE)) {
                return $companyName->getValue();
            }
        } catch (Exception $e) {
            $this->log('getECInternetCompanyName()', ['exception' => $e->getMessage()]);
        }

        return '';
    }

    /**
     * Get current customer from session
     *
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomer()
    {
        return $this->customerRepository->getById($this->customerSession->getCustomerId());
    }

    /**
     * Write to extension log.
     *
     * @param string $message
     * @param array  $extra
     */
    private function log(string $message, array $extra = [])
    {
        $this->_logger->info('Block/Widget/ECInternetCompanyName - ' . $message, $extra);
    }
}
