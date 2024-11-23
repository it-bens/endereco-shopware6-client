<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;

interface OrdersCustomFieldsUpdaterInterface
{
    public function updateOrdersCustomFields(
        OrderCollection $orders,
        OrderAddressCollection $orderAddresses,
        Context $context
    ): void;
}
