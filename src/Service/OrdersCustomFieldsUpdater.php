<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Struct\OrderCustomFields;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class OrdersCustomFieldsUpdater implements OrdersCustomFieldsUpdaterInterface
{
    private OrderCustomFieldsBuilderInterface $orderCustomFieldBuilder;
    private EntityRepository $orderRepository;

    public function __construct(
        OrderCustomFieldsBuilderInterface $orderCustomFieldBuilder,
        EntityRepository $orderRepository
    ) {
        $this->orderCustomFieldBuilder = $orderCustomFieldBuilder;
        $this->orderRepository = $orderRepository;
    }

    public function updateOrdersCustomFields(
        OrderCollection $orders,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): void {
        $orderUpdatePayloads = [];
        foreach ($orders as $order) {
            $orderCustomFields = new OrderCustomFields(
                $this->orderCustomFieldBuilder->buildOrderBillingAddressValidationData(
                    $order->getId(),
                    $orderAddresses,
                    $context
                ),
                $this->orderCustomFieldBuilder->buildOrderShippingAddressValidationData(
                    $order->getId(),
                    $orderAddresses,
                    $context
                )
            );

            $customFields = $orderCustomFields->data();
            $persistedCustomFields = $order->getCustomFields() ?? [];

            if ($orderCustomFields->compare($persistedCustomFields) === true) {
                continue;
            }

            $customFields = array_merge($customFields, $orderCustomFields->data());
            $order->setCustomFields($customFields);

            $orderUpdatePayloads[] = [
                'id' => $order->getId(),
                'customFields' => $orderCustomFields->data()
            ];
        }

        if (count($orderUpdatePayloads) === 0) {
            return;
        }

        $this->orderRepository->update($orderUpdatePayloads, $context);
    }
}
