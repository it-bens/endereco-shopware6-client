<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Entity\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

#[CoversClass(EnderecoOrderAddressExtensionEntity::class)]
class EnderecoOrderAddressExtensionEntityTest extends TestCase
{
    public static function amsStatusProvider(): \Generator
    {
        yield 'not checked status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
            false,
            false,
            false,
            false,
            false
        ];

        yield 'minor correction status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_MINOR_CORRECTION,
            true,
            false,
            true,
            false,
            false
        ];

        yield 'selected automatically status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_AUTOMATICALLY,
            true,
            false,
            false,
            true,
            false
        ];

        yield 'selected by customer status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_BY_CUSTOMER,
            true,
            false,
            false,
            false,
            true
        ];

        yield 'multiple variants status' => [
            'address_multiple_variants',
            true,
            true,
            false,
            false,
            false
        ];

        yield 'needs correction status' => [
            'address_needs_correction',
            true,
            true,
            false,
            false,
            false
        ];

        yield 'not found status' => [
            'address_not_found',
            true,
            true,
            false,
            false,
            false
        ];

        yield 'correct status' => [
            'address_correct',
            true,
            false,
            false,
            false,
            false
        ];
    }

    /**
     * Tests that createWithDefaultValues generates a valid entity with proper default values.
     * Verifies all required fields are initialized correctly and the entity ID is automatically generated.
     * This is critical for ensuring consistent entity creation across the application.
     */
    public function testCreateWithDefaultValuesGeneratesValidEntity(): void
    {
        $addressId = 'order-address-123';
        $versionId = 'version-456';
        
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues($addressId, $versionId);
        
        $this->assertNotEmpty($extension->getId());
        $this->assertSame($extension->getId(), $extension->getUniqueIdentifier());
        $this->assertSame(Defaults::LIVE_VERSION, $extension->getVersionId());
        $this->assertSame($addressId, $extension->getAddressId());
        $this->assertSame($versionId, $extension->getAddressVersionId());
        $this->assertNull($extension->getAddress());
    }

    /**
     * Tests that createWithDefaultValues handles null version ID gracefully.
     * This ensures the factory method is flexible for different Shopware contexts.
     * Version ID is optional in some scenarios and should not break entity creation.
     */
    public function testCreateWithDefaultValuesWorksWithoutVersionId(): void
    {
        $addressId = 'order-address-123';
        
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues($addressId, null);
        
        $this->assertNotEmpty($extension->getId());
        $this->assertSame($addressId, $extension->getAddressId());
        $this->assertNull($extension->getAddress());
    }

    /**
     * Tests that createWithDefaultValuesFromAddress properly extracts data from OrderAddressEntity.
     * Verifies the address entity is linked and its ID/version are correctly stored.
     * This factory method is essential for creating extensions from existing Shopware addresses.
     */
    public function testCreateWithDefaultValuesFromAddressEntity(): void
    {
        $orderAddressStub = $this->createStub(OrderAddressEntity::class);
        $orderAddressStub->method('getId')->willReturn('order-id-789');
        $orderAddressStub->method('getVersionId')->willReturn('version-abc');
        
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValuesFromAddress($orderAddressStub);
        
        $this->assertNotEmpty($extension->getId());
        $this->assertSame('order-id-789', $extension->getAddressId());
        $this->assertSame('version-abc', $extension->getAddressVersionId());
        $this->assertSame($orderAddressStub, $extension->getAddress());
    }

    /**
     * Tests that the entity is compatible with its dedicated collection class.
     * Verifies proper Shopware collection integration for managing multiple extensions.
     * Collections are critical for bulk operations and data access layer integration.
     */
    public function testEntityWorksInCollection(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $collection = new EnderecoOrderAddressExtensionCollection();
        
        $collection->add($extension);
        
        $this->assertSame(1, $collection->count());
        $this->assertSame($extension, $collection->first());
    }

    /**
     * Tests that setAddress accepts valid OrderAddressEntity instances.
     * Ensures proper type validation and storage of the associated address.
     * This relationship is fundamental for linking extensions to Shopware addresses.
     */
    public function testSetAddressAcceptsValidOrderAddressEntity(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $orderAddressStub = $this->createStub(OrderAddressEntity::class);
        
        $extension->setAddress($orderAddressStub);
        
        $this->assertSame($orderAddressStub, $extension->getAddress());
    }

    /**
     * Tests that setAddress allows clearing the address reference with null.
     * Verifies the entity can handle detachment from its associated address.
     * Null handling is important for cleanup operations and state management.
     */
    public function testSetAddressAcceptsNull(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $orderAddressStub = $this->createStub(OrderAddressEntity::class);
        
        $extension->setAddress($orderAddressStub);
        $this->assertSame($orderAddressStub, $extension->getAddress());
        
        $extension->setAddress(null);
        $this->assertNull($extension->getAddress());
    }

    /**
     * Tests that setAddress throws exception for wrong entity types.
     * Ensures type safety by rejecting non-OrderAddressEntity instances.
     * This validation prevents data corruption and maintains relationship integrity.
     */
    public function testSetAddressThrowsExceptionForInvalidEntityType(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $wrongEntityTypeStub = $this->createStub(Entity::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The address must be an instance of OrderAddressEntity.');
        
        $extension->setAddress($wrongEntityTypeStub);
    }

    /**
     * Tests that buildCartToOrderConversionData excludes system fields from output.
     * Verifies only business data is included for cart-to-order conversion process.
     * System fields like timestamps and internal references must be filtered out to prevent conflicts.
     */
    public function testBuildCartToOrderConversionDataRemovesSystemFields(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', 'version-id');
        $extension->setAmsStatus('address_correct');
        $extension->setStreet('Test Street');
        
        $data = $extension->buildCartToOrderConversionData();
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('addressId', $data);
        $this->assertArrayHasKey('amsStatus', $data);
        $this->assertArrayHasKey('street', $data);
        
        // System fields should be removed
        $this->assertArrayNotHasKey('extensions', $data);
        $this->assertArrayNotHasKey('_uniqueIdentifier', $data);
        $this->assertArrayNotHasKey('versionId', $data);
        $this->assertArrayNotHasKey('translated', $data);
        $this->assertArrayNotHasKey('createdAt', $data);
        $this->assertArrayNotHasKey('updatedAt', $data);
        $this->assertArrayNotHasKey('address', $data);
    }

    /**
     * Tests that buildDataForOrderCustomField returns identical data to cart conversion method.
     * Ensures data consistency between different export mechanisms.
     * Both methods should produce the same filtered data structure for order storage.
     */
    public function testBuildDataForOrderCustomFieldReturnsConsistentData(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', 'version-id');
        $extension->setHouseNumber('42A');
        
        $cartData = $extension->buildCartToOrderConversionData();
        $customFieldData = $extension->buildDataForOrderCustomField();
        
        $this->assertSame($cartData, $customFieldData);
    }

    /**
     * Tests that sync method copies all business data while preserving target identity.
     * Verifies complete data transfer from source to target extension.
     * Critical for maintaining data consistency during address synchronization operations.
     */
    public function testSyncCopiesAllRelevantData(): void
    {
        $sourceExtension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('source-id', null);
        $sourceExtension->setStreet('Source Street');
        $sourceExtension->setHouseNumber('123A');
        $sourceExtension->setIsPayPalAddress(true);
        $sourceExtension->setAmsStatus('address_minor_correction');
        $sourceExtension->setAmsTimestamp(1234567890);
        $sourceExtension->setAmsPredictions([['field' => 'value']]);
        $sourceExtension->setAmsRequestPayload('{"test": "data"}');
        
        $targetExtension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('target-id', null);
        $originalId = $targetExtension->getId();
        
        $targetExtension->sync($sourceExtension);
        
        // Data should be copied
        $this->assertSame('Source Street', $targetExtension->getStreet());
        $this->assertSame('123A', $targetExtension->getHouseNumber());
        $this->assertTrue($targetExtension->isPayPalAddress());
        $this->assertSame('address_minor_correction', $targetExtension->getAmsStatus());
        $this->assertSame(1234567890, $targetExtension->getAmsTimestamp());
        $this->assertSame([['field' => 'value']], $targetExtension->getAmsPredictions());
        $this->assertSame('{"test": "data"}', $targetExtension->getAmsRequestPayload());
        
        // Identity should be preserved
        $this->assertSame($originalId, $targetExtension->getId());
        $this->assertSame('target-id', $targetExtension->getAddressId());
    }

    /**
     * Tests that sync method enforces type safety for source extensions.
     * Ensures only compatible extension types can be synchronized.
     * Type validation prevents data corruption between incompatible extension entities.
     */
    public function testSyncThrowsExceptionForInvalidSourceType(): void
    {
        $targetExtension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('target-id', null);
        $invalidSource = $this->createStub(EnderecoBaseAddressExtensionEntity::class);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The address extension to sync from must be an instance of EnderecoOrderAddressExtensionEntity.');
        
        $targetExtension->sync($invalidSource);
    }

    /**
     * Tests that resetAndCreateDataForPersistence clears AMS data and returns reset state.
     * Verifies the entity is properly cleaned for fresh address validation cycles.
     * Critical for ensuring clean state when addresses need to be re-validated from scratch.
     */
    public function testResetAndCreateDataForPersistenceResetsToDefaults(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $extension->setStreet('Original Street');
        $extension->setAmsStatus('address_correct');
        $extension->setAmsTimestamp(1234567890);
        $extension->setAmsPredictions([['field' => 'value']]);
        $extension->setAmsRequestPayload('original payload');
        
        $data = $extension->resetAndCreateDataForPersistence();
        
        // Entity should be reset to defaults
        $this->assertSame('', $extension->getAmsRequestPayload());
        $this->assertSame(EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED, $extension->getAmsStatus());
        $this->assertSame([], $extension->getAmsPredictions());
        $this->assertSame(0, $extension->getAmsTimestamp());
        
        // Data array should contain reset values
        $this->assertSame('test-id', $data['addressId']);
        $this->assertSame('', $data['amsRequestPayload']);
        $this->assertSame(EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED, $data['amsStatus']);
        $this->assertSame([], $data['amsPredictions']);
        $this->assertSame(0, $data['amsTimestamp']);
    }

    /**
     * Tests that getUniqueIdentifier returns the entity's internal ID by default.
     * Verifies standard behavior when no custom unique identifier is set.
     * This is the primary identification mechanism for Shopware entity management.
     */
    public function testGetUniqueIdentifierReturnsEntityId(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('address-id', null);
        
        $this->assertSame($extension->getId(), $extension->getUniqueIdentifier());
    }

    /**
     * Tests that getUniqueIdentifier falls back to address ID when entity ID is not set.
     * Ensures reliable identification even for partially constructed entities.
     * Fallback mechanism is crucial for entity identification during creation processes.
     */
    public function testGetUniqueIdentifierFallbackToAddressId(): void
    {
        $extension = new EnderecoOrderAddressExtensionEntity();
        $addressId = 'test-order-address-fallback';
        $extension->setAddressId($addressId);
        
        $this->assertSame($addressId, $extension->getUniqueIdentifier());
    }

    /**
     * Tests that manually set unique identifier takes precedence over default logic.
     * Verifies custom identification can override both entity ID and address ID.
     * Custom identifiers enable advanced entity management and tracking scenarios.
     */
    public function testManuallySetUniqueIdentifierOverridesDefault(): void
    {
        $addressId = 'order-address-123';
        $customUniqueId = 'custom-order-unique-456';
        
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues($addressId, null);
        $originalId = $extension->getId();
        
        $extension->setUniqueIdentifier($customUniqueId);
        
        $this->assertSame($customUniqueId, $extension->getUniqueIdentifier());
        $this->assertNotSame($originalId, $extension->getUniqueIdentifier());
        $this->assertNotSame($addressId, $extension->getUniqueIdentifier());
    }

    /**
     * Tests that getUniqueIdentifier always returns a non-null, non-empty value.
     * Ensures entity identification is always possible under any circumstances.
     * Reliable identification is critical for Shopware's entity management system.
     */
    public function testGetUniqueIdentifierNeverReturnsNull(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('any-order-id', null);

        $uniqueId = $extension->getUniqueIdentifier();

        $this->assertIsString($uniqueId);
        $this->assertNotEmpty($uniqueId);
    }

    /**
     * Tests that business logic status methods return correct values for all AMS status variations.
     * Verifies the address validation state machine logic across all possible states.
     * These methods are critical for frontend decision-making and workflow control.
     */
    #[DataProvider('amsStatusProvider')]
    public function testBusinessLogicStatusMethods(
        string $amsStatus,
        bool $expectedIsChecked,
        bool $expectedNeedsCorrection,
        bool $expectedHasMinorCorrection,
        bool $expectedIsSelectedAutomatically,
        bool $expectedIsSelectedByCustomer
    ): void {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', null);
        $extension->setAmsStatus($amsStatus);

        $this->assertSame($expectedIsChecked, $extension->isAddressChecked());
        $this->assertSame($expectedNeedsCorrection, $extension->needsCorrectionInFrontend());
        $this->assertSame($expectedHasMinorCorrection, $extension->hasMinorCorrection());
        $this->assertSame($expectedIsSelectedAutomatically, $extension->isSelectedAutomatically());
        $this->assertSame($expectedIsSelectedByCustomer, $extension->isSelectedByCustomer());
    }

    /**
     * Tests that entity is created with proper default values for all properties.
     * Verifies consistent initialization state across all extension fields.
     * Default values ensure predictable behavior and prevent undefined state issues.
     */
    public function testEntityDefaultValues(): void
    {
        $extension = EnderecoOrderAddressExtensionEntity::createWithDefaultValues('test-id', 'version-id');

        $this->assertNotEmpty($extension->getId());
        $this->assertSame('test-id', $extension->getAddressId()); 
        $this->assertSame('version-id', $extension->getAddressVersionId());
        $this->assertSame('', $extension->getAmsRequestPayload());
        $this->assertSame(EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED, $extension->getAmsStatus());
        $this->assertSame(0, $extension->getAmsTimestamp());
        $this->assertSame([], $extension->getAmsPredictions());
        $this->assertFalse($extension->isPayPalAddress());
        $this->assertFalse($extension->isAmazonPayAddress());
        $this->assertSame('', $extension->getStreet());
        $this->assertSame('', $extension->getHouseNumber());
        $this->assertNull($extension->getAddress());
    }
}
