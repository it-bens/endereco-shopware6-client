<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class EnderecoCustomerAddressExtensionEntity
 *
 * Represents a custom entity for managing Endereco address verification extensions
 * specifically for customer addresses in Shopware 6.
 *
 * This class extends the base address extension functionality to provide:
 * - Association management with Shopware customer addresses
 * - Data conversion capabilities for order processing
 * - Address verification status and metadata management
 *
 * @package Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress
 * @author Michal Daniel
 * @author Ilja Weber
 * @author Martin Bens
 *
 * @property-read CustomerAddressEntity|null $address The associated customer address entity
 * @property-read string $addressId The ID of the associated customer address
 */
class EnderecoCustomerAddressExtensionEntity extends EnderecoBaseAddressExtensionEntity
{
    /** @var ?CustomerAddressEntity The associated customer address entity. */
    protected ?CustomerAddressEntity $address = null;

    /**
     * The constructor should not be used outside this class.
     * Use the EnderecoCustomerAddressExtensionEntity::createWithDefaultValues
     * or the EnderecoCustomerAddressExtensionEntity::createWithDefaultValuesFromAddress method instead.
     * This ensures the integrate of the entity.
     */
    public function __construct() {}

    /**
     * Creates a customer address extension instance with default values and proper unique identifier.
     * The following properties are set:
     * - address ID: as passed
     * - unique identifier: previously set address ID
     *
     * ⚠️ The address is not set in the returned extension and has to be set manually.
     * 
     * @param string $addressId The customer address ID to associate with
     * @return EnderecoCustomerAddressExtensionEntity
     */
    public static function createWithDefaultValues(string $addressId): EnderecoCustomerAddressExtensionEntity
    {
        $addressExtension = new EnderecoCustomerAddressExtensionEntity();
        $addressExtension->setAddressId($addressId);
        $addressExtension->setUniqueIdentifier($addressId);

        return $addressExtension;
    }

    /**
     * Creates an order address extension instance with a random UUID and the default AMS data.
     * The address ID and version ID are drawn from the passed address.
     *
     * The following properties are set:
     * - address ID: as passed
     * - unique identifier: previously set address ID
     *
     * ✅ The address is set in the returned extension and does not have to be set manually.
     *
     * @param CustomerAddressEntity $addressEntity
     * @return EnderecoCustomerAddressExtensionEntity
     */
    public static function createWithDefaultValuesFromAddress(
        CustomerAddressEntity $addressEntity
    ): EnderecoCustomerAddressExtensionEntity {
        $addressExtension = self::createWithDefaultValues($addressEntity->getId());
        $addressExtension->setAddress($addressEntity);

        return $addressExtension;
    }

    /**
     * Gets the associated customer address entity.
     *
     * @return CustomerAddressEntity|null The associated customer address entity or null if not set
     */
    public function getAddress(): ?CustomerAddressEntity
    {
        return $this->address;
    }

    /**
     * Set the associated customer address entity.
     *
     * @param Entity|null $address The associated customer address entity to set.
     */
    public function setAddress(?Entity $address): void
    {
        if ($address !== null && !$address instanceof CustomerAddressEntity) {
            throw new \InvalidArgumentException('The address must be an instance of CustomerAddressEntity.');
        }

        $this->address = $address;
    }

    /**
     * Creates a new order address extension entity based on this customer address extension.
     * Copies all relevant address verification data to the new order address extension.
     *
     * This method is used when converting customer addresses to order addresses during
     * the checkout process, ensuring all Endereco verification data is preserved.
     *
     * ⚠️ The address is not set in the returned extension and has to be set manually
     * if the method is used for other purposes.
     *
     * The following properties of the order address extension are set:
     * - ID: random UUID
     * - unique identifier: previously set ID
     * - version ID: default live version ID (0fa91ce3e96a4bc2be4bd9ce752c3425)
     * - address ID: as passed
     *
     * @param string $orderAddressId The ID of the new order address to associate with
     * @return EnderecoOrderAddressExtensionEntity A new order address extension populated with this entity's data
     */
    public function createOrderAddressExtension(string $orderAddressId): EnderecoOrderAddressExtensionEntity
    {
        $entity = EnderecoOrderAddressExtensionEntity::createWithDefaultValues($orderAddressId, null);
        $entity->setAmsStatus($this->getAmsStatus());
        $entity->setAmsTimestamp($this->getAmsTimestamp());
        $entity->setAmsPredictions($this->getAmsPredictions());
        $entity->setAmsRequestPayload($this->getAmsRequestPayload());
        $entity->setIsPayPalAddress($this->isPayPalAddress());
        $entity->setIsAmazonPayAddress($this->isAmazonPayAddress());
        $entity->setStreet($this->getStreet());
        $entity->setHouseNumber($this->getHouseNumber());

        return $entity;
    }

    /**
     * Syncs the data of this address extension entity with the data of another address extension entity.
     */
    public function sync(EnderecoCustomerAddressExtensionEntity $addressExtensionToSyncFrom): void
    {
        $this->setStreet($addressExtensionToSyncFrom->getStreet());
        $this->setHouseNumber($addressExtensionToSyncFrom->getHouseNumber());
        $this->setIsPayPalAddress($addressExtensionToSyncFrom->isPayPalAddress());
        $this->setIsAmazonPayAddress($addressExtensionToSyncFrom->isAmazonPayAddress());
        $this->setAmsRequestPayload($addressExtensionToSyncFrom->getAmsRequestPayload());
        $this->setAmsStatus($addressExtensionToSyncFrom->getAmsStatus());
        $this->setAmsPredictions($addressExtensionToSyncFrom->getAmsPredictions());
        $this->setAmsTimestamp($addressExtensionToSyncFrom->getAmsTimestamp());
    }

}
