<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Order\OrderEntity;

interface OrderCustomFieldsBuilderInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildOrderAddressValidationData(OrderEntity $orderEntity): array;
}
