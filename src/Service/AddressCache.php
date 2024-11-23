<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;

final class AddressCache implements AddressCacheInterface
{
    /** @var array<string, CustomerAddressEntity|OrderAddressEntity> $addressEntities */
    private array $addressEntities = [];

    public function get(string $addressEntityId): CustomerAddressEntity|OrderAddressEntity|null
    {
        return $this->addressEntities[$addressEntityId] ?? null;
    }

    public function has(string $addressEntityId): bool
    {
        return array_key_exists($addressEntityId, $this->addressEntities);
    }

    public function set(CustomerAddressEntity|OrderAddressEntity $addressEntity): void
    {
        $this->addressEntities[$addressEntity->getId()] = $addressEntity;
    }
}
