<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Ui\Component\Listing\Column;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Exception;

/**
 * ECInternetCompanyName Column
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ECInternetCompanyName extends Column
{
    const COLUMN_SOURCE_ATTRIBUTE_CODE = 'ecinternet_company_name';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * ECInternetCompanyName constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory           $uiComponentFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface                  $orderRepository
     * @param array                                                        $components
     * @param array                                                        $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->_customerRepository = $customerRepository;
        $this->_orderRepository    = $orderRepository;
    }

    /**
     * Add 'ecinternet_company_name' data
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                /** @var \Magento\Sales\Api\Data\OrderInterface $order */
                $order = $this->_orderRepository->get($item['entity_id']);

                // Extract ecinternet_company_name
                $ecinternetCompanyName = $this->getECInternetCompanyName($order);

                // Assign to item
                $item[$this->getData('name')] = $ecinternetCompanyName;
            }
        }

        return $dataSource;
    }

    /**
     * Retrieve 'ecinternet_company_name' attribute value from Customer using 'customer_id' from the Order.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return string
     */
    private function getECInternetCompanyName(
        OrderInterface $order
    ) {
        if ($customerId = $order->getCustomerId()) {
            /** @noinspection PhpCastIsUnnecessaryInspection */
            if ($customer = $this->getCustomer((int)$customerId)) {
                if ($ecinternetCompanyName = $customer->getCustomAttribute(self::COLUMN_SOURCE_ATTRIBUTE_CODE)) {
                    return $ecinternetCompanyName->getValue();
                }
            }
        }

        return '';
    }

    /**
     * Retrieve Customer using CustomerRepository.
     *
     * @param int $customerId
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function getCustomer(int $customerId)
    {
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (Exception $e) {
            error_log("getCustomer() - Unable to lookup customer by id: {$e->getMessage()}");
        }

        return null;
    }
}
