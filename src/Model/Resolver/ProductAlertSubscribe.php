<?php

/**
 * @category    ScandiPWA
 * @package     ScandiPWA_ProductAlertsGraphQl
 * @copyright   Copyright 2014 Adobe. All Rights Reserved.
 * @copyright   Copyright © 2021 Scandiweb, Ltd (https://scandiweb.com)
 * @copyright   Modifications © Selveq. All rights reserved.
 * @license     OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace ScandiPWA\ProductAlertsGraphQl\Model\Resolver;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\ProductAlert\Model\PriceFactory as PriceAlertFactory;
use Magento\ProductAlert\Model\StockFactory as StockAlertFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductAlertSubscribe implements ResolverInterface
{
    public const string PRODUCT_ALERT_PRICE_DROP = 'PRODUCT_ALERT_PRICE_DROP';
    public const string PRODUCT_ALERT_IN_STOCK = 'PRODUCT_ALERT_IN_STOCK';

    // the theme sends these two values verbatim, so they are the wire contract and cannot be changed
    private const array SUPPORTED_TYPES = [
        self::PRODUCT_ALERT_PRICE_DROP,
        self::PRODUCT_ALERT_IN_STOCK,
    ];

    /**
     * @param PriceAlertFactory $priceAlertFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StockAlertFactory $stockAlertFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PriceAlertFactory $priceAlertFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockAlertFactory $stockAlertFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        /** @var ContextInterface $context */
        $customerId = (int)$context->getUserId();

        if ($customerId <= 0) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $type = $args['type'];

        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            // the refusal names the accepted values, never the input, so a payload cannot echo itself back
            $this->logger->info(sprintf('productAlertSubscribe: unknown type rejected, length %d', strlen($type)));

            throw new GraphQlInputException(
                __('Unknown product alert type. Accepted values: %1.', implode(', ', self::SUPPORTED_TYPES))
            );
        }

        $productId = (int)$args['productId'];
        $store = $this->storeManager->getStore();

        if ($type === self::PRODUCT_ALERT_PRICE_DROP) {
            return $this->priceDropSubscribe($productId, $customerId, $store);
        }

        return $this->inStockSubscribe($productId, $customerId, $store);
    }

    /**
     * subscribe a customer to in-stock alerts for a product
     * @param int $productId
     * @param int $customerId
     * @param StoreInterface $store
     * @return bool
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     */
    private function inStockSubscribe(int $productId, int $customerId, StoreInterface $store): bool
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
            $model = $this->stockAlertFactory->create()
                ->setCustomerId($customerId)
                ->setProductId($product->getId())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreId($store->getId());
            $model->save();
        } catch (NoSuchEntityException) {
            throw new GraphQlNoSuchEntityException(__('Product doesn\'t exist'));
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw new GraphQlInputException(
                __("The alert subscription couldn't update at this time. Please try again later.")
            );
        }

        return true;
    }

    /**
     * subscribe a customer to price-drop alerts for a product
     * @param int $productId
     * @param int $customerId
     * @param StoreInterface $store
     * @return bool
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     */
    private function priceDropSubscribe(int $productId, int $customerId, StoreInterface $store): bool
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
            $model = $this->priceAlertFactory->create()
                ->setCustomerId($customerId)
                ->setProductId($product->getId())
                ->setPrice($product->getFinalPrice())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreId($store->getId());
            $model->save();
        } catch (NoSuchEntityException) {
            throw new GraphQlNoSuchEntityException(__('Product doesn\'t exist'));
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw new GraphQlInputException(
                __("The alert subscription couldn't update at this time. Please try again later.")
            );
        }

        return true;
    }
}
