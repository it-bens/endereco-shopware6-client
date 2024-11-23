<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;

interface AddressCacheInterface
{
    public function get(string $addressEntityId): CustomerAddressEntity|OrderAddressEntity|null;

    public function has(string $addressEntityId): bool;

    public function set(CustomerAddressEntity|OrderAddressEntity $addressEntity): void;
}
