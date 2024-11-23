<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Model\AddressCheckData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

/**
 * Class EnderecoCustomerAddressExtensionEntity.
 *
 * @author Michal Daniel
 * @author Ilja Weber
 * @author Martin Bens
 *
 * This class provides a custom entity to manage extensions for the Order Address object in the context
 * of the Endereco plugin.
 *
 * @phpstan-import-type AddressCheckDataData from AddressCheckData
 */
class EnderecoOrderAddressExtensionEntity extends EnderecoBaseAddressExtensionEntity
{
    /** @var ?OrderAddressEntity The associated order address entity. */
    protected ?OrderAddressEntity $address = null;

    /** @var ?string The ID of the customer address which contained the original validation data. */
    protected ?string $originalCustomerAddressId = null;

    /** @var ?CustomerAddressEntity The customer address which contained the original validation data. */
    protected ?CustomerAddressEntity $originalCustomerAddress = null;

    /**
     * Get the associated order address entity.
     *
     * @return OrderAddressEntity|null The associated order address entity, or null if none is set.
     */
    public function getAddress(): ?OrderAddressEntity
    {
        return $this->address;
    }

    /**
     * Set the associated order address entity.
     *
     * @param OrderAddressEntity|null $address The associated order address entity to set.
     */
    public function setAddress(?Entity $address): void
    {
        if (!$address instanceof OrderAddressEntity) {
            throw new \InvalidArgumentException('The address must be an instance of OrderAddressEntity.');
        }

        $this->address = $address;
    }

    /**
     * Get original customer address ID.
     *
     * @return ?string The ID of the customer address which contained the original validation data.
     */
    public function getOriginalCustomerAddressId(): ?string
    {
        return $this->originalCustomerAddressId;
    }

    /**
     * Set original customer address ID.
     *
     * @param ?string $originalCustomerAddressId The ID of the customer address
     *                                           which contained the original validation data.
     */
    public function setOriginalCustomerAddressId(?string $originalCustomerAddressId): void
    {
        $this->originalCustomerAddressId = $originalCustomerAddressId;
    }

    /**
     * Get the original customer order address entity.
     *
     * @return CustomerAddressEntity|null The customer address which contained the original validation data.
     */
    public function getOriginalCustomerAddress(): ?CustomerAddressEntity
    {
        return $this->originalCustomerAddress;
    }

    /**
     * Set the original customer order address entity.
     *
     * @param CustomerAddressEntity|null $originalCustomerAddress The customer address
     *                                                            which contained the original validation data.
     */
    public function setOriginalCustomerAddress(?CustomerAddressEntity $originalCustomerAddress): void
    {
        $this->originalCustomerAddress = $originalCustomerAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCartToOrderConversionData(): array
    {
        $data = $this->getVars();
        unset($data['extensions']);
        unset($data['_uniqueIdentifier']);
        unset($data['versionId']);
        unset($data['translated']);
        unset($data['createdAt']);
        unset($data['updatedAt']);
        unset($data['address']);
        unset($data['originalCustomerAddress']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDataForOrderCustomField(): array
    {
        // The required data is currently the same. This might change in the future.
        return $this->buildCartToOrderConversionData();
    }

    /**
     * Syncs the data of this address extension entity with the data of another address extension entity.
     */
    public function sync(EnderecoBaseAddressExtensionEntity $addressExtensionToSyncFrom): void
    {
        if (!$addressExtensionToSyncFrom instanceof self) {
            throw new \InvalidArgumentException(
                'The address extension to sync from must be an instance of EnderecoOrderAddressExtensionEntity.'
            );
        }

        $this->setStreet($addressExtensionToSyncFrom->getStreet());
        $this->setHouseNumber($addressExtensionToSyncFrom->getHouseNumber());
        $this->setIsPayPalAddress($addressExtensionToSyncFrom->isPayPalAddress());
        $this->setAmsRequestPayload($addressExtensionToSyncFrom->getAmsRequestPayload());
        $this->setAmsStatus($addressExtensionToSyncFrom->getAmsStatus());
        $this->setAmsPredictions($addressExtensionToSyncFrom->getAmsPredictions());
        $this->setAmsTimestamp($addressExtensionToSyncFrom->getAmsTimestamp());
    }

    /**
     * Resets the data of this address extension entity to default values and creates a data array for persistence.
     *
     * @return array{
     *     addressId: string,
     *     amsRequestPayload: ?AddressCheckDataData,
     *     amsStatus: string,
     *     amsPredictions: array<array<string, string>>,
     *     amsTimestamp: int
     * }
     */
    public function resetAndCreateDataForPersistence(): array
    {
        // The AMS request payload will be updated by a listener.
        $this->setAmsRequestPayload(null);
        $this->setAmsStatus(EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED);
        $this->setAmsPredictions([]);
        $this->setAmsTimestamp(0);

        return [
            'addressId' => $this->getAddressId(),
            'amsRequestPayload' => null,
            'amsStatus' => EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
            'amsPredictions' => [],
            'amsTimestamp' => 0,
        ];
    }
}
