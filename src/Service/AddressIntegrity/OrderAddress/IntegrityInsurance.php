<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

interface IntegrityInsurance
{
    public static function getPriority(): int;

    public function ensure(
        OrderAddressEntity $addressEntity,
        string $salesChannelId,
        Context $context
    ): void;
}
