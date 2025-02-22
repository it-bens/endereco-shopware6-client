<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Model;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

interface AddressPersistenceStrategy
{
    /**
     * The method is used in the street-split insurance to provide the update/upsert payload for the persistence
     * of the address extension and the address itself (via relationship writing).
     *
     * @return array{
     *     addressId: string,
     *     street: string,
     *     houseNumber: string,
     *     address?: array{
     *         id: string,
     *         street?: string,
     *         additionalAddressLine1?: string|null,
     *         additionalAddressLine2?: string|null,
     *    }
     * }
     */
    public function buildAddressExtensionWithAddressUpsertPayload(
        string $addressId,
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo
    ): array;

    /**
     * The method is used to fetch the correct field for the additional info that is used
     * for the street-split service of Endereco.
     */
    public function getAdditionalInfoForStreetSplit(CustomerAddressEntity $addressEntity): string;

    /**
     * The method is used to check if the persistence of the address and it's extension is required
     * based on the passed data. This prevents unnecessary write operations that could potentially trigger other events.
     */
    public function isAddressExtensionWithAddressPersistenceRequired(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo,
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $extension
    ): bool;

    /**
     * The method is used to update the address entity and it's extension based on the passed data.
     */
    public function updateAddress(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo,
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $extension
    ): void;

    /**
     * The method is used to update the address data array (normalized data from an address form)
     * based on the passed data.
     *
     * @param array<string, mixed> $addressData
     */
    public function updateAddressData(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        string $additionalInfo,
        array &$addressData
    ): void;
}
