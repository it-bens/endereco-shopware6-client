<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

interface OrderAddressIntegrityInsuranceInterface
{
    /**
     * Ensure:
     * the address extension exists,
     * the street is split,
     * the AMS status is still valid
     *
     * @param OrderAddressEntity $addressEntity
     * @param string $salesChannelId
     * @param Context $context
     */
    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void;
}