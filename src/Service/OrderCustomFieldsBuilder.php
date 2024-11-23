<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;

final class OrderCustomFieldsBuilder implements OrderCustomFieldsBuilderInterface
{
    private EntityRepository $orderAddressRepository;

    public function __construct(EntityRepository $orderAddressRepository)
    {
        $this->orderAddressRepository = $orderAddressRepository;
    }

    public function buildOrderBillingAddressValidationData(
        string $orderId,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addAggregation(new FilterAggregation(
            'billing-address-ids',
            new TermsAggregation('filtered-billing-address-ids', 'order.billingAddressId'),
            [
                new EqualsFilter('orderId', $orderId),
                new OrFilter([
                    new EqualsAnyFilter('order.billingAddressId', $orderAddresses->getIds()),
                    new EqualsAnyFilter('order.deliveries.shippingOrderAddressId', $orderAddresses->getIds())
                ])
            ],
        ));

        /** @var TermsResult $billingAddressIdsResult */
        $billingAddressIdsResult = $this->orderAddressRepository
            ->search($criteria, $context)
            ->getAggregations()
            ->get('filtered-billing-address-ids');
        $billingAddressIds = $billingAddressIdsResult->getKeys();
        $billingAddresses = $orderAddresses->filter(static function ($orderAddress) use ($billingAddressIds) {
            return in_array($orderAddress->getId(), $billingAddressIds);
        });

        $orderAddressExtensionCollection = new EnderecoOrderAddressExtensionCollection();

        foreach ($billingAddresses as $orderAddressEntity) {
            $this->addOrderAddressExtensionToCollection($orderAddressEntity, $orderAddressExtensionCollection);
        }

        return $orderAddressExtensionCollection->buildDataForOrderCustomField();
    }

    public function buildOrderShippingAddressValidationData(
        string $orderId,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addAggregation(new FilterAggregation(
            'shipping-address-ids',
            new TermsAggregation('filtered-shipping-address-ids', 'order.deliveries.shippingOrderAddressId'),
            [
                new EqualsFilter('orderId', $orderId),
                new OrFilter([
                    new EqualsAnyFilter('order.billingAddressId', $orderAddresses->getIds()),
                    new EqualsAnyFilter('order.deliveries.shippingOrderAddressId', $orderAddresses->getIds())
                ])
            ],
        ));

        /** @var TermsResult $shippingAddressIdsResult */
        $shippingAddressIdsResult = $this->orderAddressRepository
            ->search($criteria, $context)
            ->getAggregations()
            ->get('filtered-shipping-address-ids');
        $shippingAddressIds = $shippingAddressIdsResult->getKeys();
        $shippingAddresses = $orderAddresses->filter(static function ($orderAddress) use ($shippingAddressIds) {
            return in_array($orderAddress->getId(), $shippingAddressIds);
        });

        $orderAddressExtensionCollection = new EnderecoOrderAddressExtensionCollection();

        foreach ($shippingAddresses as $orderAddressEntity) {
            $this->addOrderAddressExtensionToCollection($orderAddressEntity, $orderAddressExtensionCollection);
        }

        return $orderAddressExtensionCollection->buildDataForOrderCustomField();
    }

    private function addOrderAddressExtensionToCollection(
        OrderAddressEntity $orderAddressEntity,
        EnderecoOrderAddressExtensionCollection $orderAddressExtensionCollection
    ): void {
        $orderAddressExtension = $orderAddressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);
        if ($orderAddressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            $orderAddressExtensionCollection->set(
                $orderAddressExtension->getAddressId(),
                $orderAddressExtension
            );
        }
    }
}
