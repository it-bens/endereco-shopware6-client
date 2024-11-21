<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Order\OrderEntity;

interface OrderCustomFieldsBuilderInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildOrderBillingAddressValidationData(OrderEntity $orderEntity): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildOrderShippingAddressValidationData(OrderEntity $orderEntity): array;
}
