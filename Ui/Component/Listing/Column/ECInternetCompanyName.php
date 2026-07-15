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
use ECInternet\CustomerFeatures\Model\Config;
use Exception;

/**
 * ECInternetCompanyName Column
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ECInternetCompanyName extends Column
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

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
        $this->customerRepository = $customerRepository;
        $this->orderRepository    = $orderRepository;

        parent::__construct($context, $uiComponentFactory, $components, $data);
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
                if (is_numeric($item['entity_id'])) {
                    /** @var \Magento\Sales\Api\Data\OrderInterface $order */
                    if ($order = $this->getOrder((int)$item['entity_id'])) {
                        // Extract ecinternet_company_name and assign to item
                        $item[$this->getData('name')] = $this->getECInternetCompanyName($order);
                    }
                }
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
                if ($ecinternetCompanyName = $customer->getCustomAttribute(Config::ATTRIBUTE_CUSTOMER_COMPANY_NAME)) {
                    return $ecinternetCompanyName->getValue();
                }
            }
        }

        return '';
    }

    /**
     * Retrieve OrderInterface using OrderRepository.
     *
     * @param int $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function getOrder(int $orderId)
    {
        try {
            return $this->orderRepository->get($orderId);
        } catch (Exception $e) {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log("getOrder() - Unable to lookup order by id: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Retrieve CustomerInterface using CustomerRepository.
     *
     * @param int $customerId
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function getCustomer(int $customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (Exception $e) {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log("getCustomer() - Unable to lookup customer by id: {$e->getMessage()}");
        }

        return null;
    }
}
