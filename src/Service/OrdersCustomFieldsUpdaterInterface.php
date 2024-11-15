<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Framework\Context;

interface OrdersCustomFieldsUpdaterInterface
{
    /**
     * @param string[] $orderIds
     * @param string[] $orderAddressIds
     */
    public function updateOrdersCustomFields(array $orderIds, array $orderAddressIds, Context $context): void;
}
