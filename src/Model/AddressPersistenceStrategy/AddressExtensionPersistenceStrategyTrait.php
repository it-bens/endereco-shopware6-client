<?php

namespace Endereco\Shopware6Client\Model\AddressPersistenceStrategy;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

trait AddressExtensionPersistenceStrategyTrait
{
    /**
     * @return array{
     *     addressId: string,
     *     street: string,
     *     houseNumber: string,
     * }
     */
    private function buildAddressExtensionUpsertPayload(
        string $addressId,
        string $streetName,
        string $houseNumber
    ): array {
        return [
            'addressId' => $addressId,
            'street' => $streetName,
            'houseNumber' => $houseNumber,
        ];
    }

    private function isAddressExtensionPersistenceRequired(
        string $streetName,
        string $houseNumber,
        EnderecoCustomerAddressExtensionEntity $extension
    ): bool {
        if ($extension->getStreet() !== $streetName) {
            return true;
        }

        if ($extension->getHouseNumber() !== $houseNumber) {
            return true;
        }

        return false;
    }

    private function updateAddressExtension(
        string $streetName,
        string $houseNumber,
        EnderecoCustomerAddressExtensionEntity $extension
    ): void {
        $extension->setStreet($streetName);
        $extension->setHouseNumber($houseNumber);
    }
}
