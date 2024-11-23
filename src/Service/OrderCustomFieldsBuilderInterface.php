<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Framework\Context;

interface OrderCustomFieldsBuilderInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildOrderBillingAddressValidationData(
        string $orderId,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildOrderShippingAddressValidationData(
        string $orderId,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): array;
}
