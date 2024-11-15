<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Struct\OrderCustomFields;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Api\Sync\SyncBehavior;
use Shopware\Core\Framework\Api\Sync\SyncOperation;
use Shopware\Core\Framework\Api\Sync\SyncServiceInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;

final class OrdersCustomFieldsUpdater implements OrdersCustomFieldsUpdaterInterface
{
    private EntityRepository $orderRepository;
    private OrderCustomFieldsBuilderInterface $orderCustomFieldBuilder;
    private SyncServiceInterface $syncService;

    public function __construct(
        EntityRepository $orderRepository,
        OrderCustomFieldsBuilderInterface $orderCustomFieldBuilder,
        SyncServiceInterface $syncService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderCustomFieldBuilder = $orderCustomFieldBuilder;
        $this->syncService = $syncService;
    }

    public function updateOrdersCustomFields(array $orderIds, array $orderAddressIds, Context $context): void
    {
        $criteria = new Criteria();

        if (count($orderIds) !== 0) {
            $criteria->addFilter(new EqualsAnyFilter('id', $orderIds));
        }

        if (count($orderAddressIds) !== 0) {
            $criteria->addFilter(new OrFilter([
                new EqualsAnyFilter('addresses.id', $orderAddressIds),
                new EqualsAnyFilter('deliveries.shippingOrderAddress.id', $orderAddressIds)
            ]));
        }

        $criteria->addAssociation('addresses');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        // The order address extensions are automatically loaded.

        /** @var OrderCollection $orderCollection */
        $orderCollection = $this->orderRepository->search($criteria, $context);

        $orderSyncPayload = [];
        foreach ($orderCollection as $orderEntity) {
            $orderAddressValidationData = $this->orderCustomFieldBuilder->buildOrderAddressValidationData($orderEntity);
            $orderCustomFields = new OrderCustomFields($orderAddressValidationData);

            $orderSyncPayload[] = [
                'id' => $orderEntity->getId(),
                'customFields' => $orderCustomFields->data()
            ];
        }

        if (count($orderSyncPayload) === 0) {
            return;
        }

        $syncOperator = new SyncOperation(
            'write_order_custom_fields_from_endereco_order_address_ext_written',
            OrderDefinition::ENTITY_NAME,
            SyncOperation::ACTION_UPSERT,
            $orderSyncPayload
        );
        $this->syncService->sync([$syncOperator], $context, new SyncBehavior());
    }
}
