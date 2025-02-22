<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Model\AddressPersistenceStrategy;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

/**
 * The strategy is used if it's allowed to overwrite the native Shopware address fields
 * but no "additionalAddressLine" field is enabled in Shopware.
 */
final class WithAllowedNativeAddressOverrideNotIncludeAdditionalAddressLine implements AddressPersistenceStrategy
{
    use AddressExtensionPersistenceStrategyTrait;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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
        ];

        return $addressExtensionPayload;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAdditionalInfoForStreetSplit(CustomerAddressEntity $addressEntity): string
    {
        return '';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateAddress(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        ?string $additionalInfo,
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $extension
    ): void {
        $addressEntity->setStreet($fullStreet);

        $this->updateAddressExtension($streetName, $houseNumber, $extension);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateAddressData(
        string $fullStreet,
        string $streetName,
        string $houseNumber,
        string $additionalInfo,
        array &$addressData
    ): void {
        $addressData['street'] = $fullStreet;

        $addressData['extensions']['enderecoAddress']['streetName'] = $streetName;
        $addressData['extensions']['enderecoAddress']['houseNumber'] = $houseNumber;
    }
}
