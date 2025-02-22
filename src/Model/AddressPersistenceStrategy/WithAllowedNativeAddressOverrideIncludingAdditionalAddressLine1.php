<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Model\AddressPersistenceStrategy;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

/**
 * The strategy is used if it's allowed to overwrite the native Shopware address fields
 * and if the "additionalAddressLine1" field is enabled in Shopware.
 */
final class WithAllowedNativeAddressOverrideIncludingAdditionalAddressLine1 implements AddressPersistenceStrategy
{
    use AddressExtensionPersistenceStrategyTrait;

    public function buildAddressExtensionWithAddressUpsertPayload(
        string $addressId,
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo
    ): array {
        $addressExtensionPayload = $this->buildAddressExtensionUpsertPayload($addressId, $streetName, $houseNumber);
        $addressExtensionPayload['address'] = [
            'id' => $addressId,
            'street' => $fullStreet,
            'additionalAddressLine1' => $additionalInfo
        ];

        return $addressExtensionPayload;
    }

    public function getAdditionalInfoForStreetSplit(CustomerAddressEntity $addressEntity): string
    {
        return $addressEntity->getAdditionalAddressLine1() ?? '';
    }

    public function isAddressExtensionWithAddressPersistenceRequired(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo,
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $extension
    ): bool {
        if ($this->isAddressExtensionPersistenceRequired($streetName, $houseNumber, $extension) === true) {
            return true;
        }

        if ($addressEntity->getStreet() !== $fullStreet) {
            return true;
        }

        if ($addressEntity->getAdditionalAddressLine1() !== $additionalInfo) {
            return true;
        }

        return false;
    }

    public function updateAddress(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo,
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $extension
    ): void {
        $addressEntity->setStreet($fullStreet);

        // Shopware doesn't allow setting the additional address lines fields to null.
        // As the "additionalField" value comes from Endereco, it can be assumed that removing an existing
        // additional address line value is not intended.
        if (is_string($additionalInfo) === true) {
            $addressEntity->setAdditionalAddressLine1($additionalInfo);
        }

        $this->updateAddressExtension($streetName, $houseNumber, $extension);
    }

    public function updateAddressData(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        string $additionalInfo,
        array &$addressData
    ): void {
        $addressData['street'] = $fullStreet;
        $addressData['additionalAddressLine1'] = $additionalInfo;

        $addressData['extensions']['enderecoAddress']['streetName'] = $streetName;
        $addressData['extensions']['enderecoAddress']['houseNumber'] = $houseNumber;
    }
}
