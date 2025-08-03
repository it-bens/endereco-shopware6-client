<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Entity\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

#[CoversClass(EnderecoCustomerAddressExtensionEntity::class)]
class EnderecoCustomerAddressExtensionEntityTest extends TestCase
{
    /**
     * Provides test data for AMS status scenarios.
     *
     * @return \Generator<string, array{string, bool, bool, bool, bool, bool}>
     */
    public static function amsStatusProvider(): \Generator
    {
        yield 'not checked status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
            false, // isAddressChecked
            false, // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'minor correction status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_MINOR_CORRECTION,
            true,  // isAddressChecked
            false, // needsCorrectionInFrontend
            true,  // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'selected automatically status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_AUTOMATICALLY,
            true,  // isAddressChecked
            false, // needsCorrectionInFrontend
            false, // hasMinorCorrection
            true,  // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'selected by customer status' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_BY_CUSTOMER,
            true,  // isAddressChecked
            false, // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            true   // isSelectedByCustomer
        ];

        yield 'multiple variants status' => [
            'address_multiple_variants',
            true,  // isAddressChecked
            true,  // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'needs correction status' => [
            'address_needs_correction',
            true,  // isAddressChecked
            true,  // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'not found status' => [
            'address_not_found',
            true,  // isAddressChecked
            true,  // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];

        yield 'correct status' => [
            'address_correct',
            true,  // isAddressChecked
            false, // needsCorrectionInFrontend
            false, // hasMinorCorrection
            false, // isSelectedAutomatically
            false  // isSelectedByCustomer
        ];
    }

    /**
     * Provides test data for house number edge cases.
     *
     * @return \Generator<string, array{string, string}>
     */
    public static function houseNumberEdgeCaseProvider(): \Generator
    {
        yield 'empty house number' => ['', ''];
        yield 'whitespace house number' => ['  ', '  '];
        yield 'numeric house number' => ['123', '123'];
        yield 'alphanumeric house number' => ['123A', '123A'];
    }

    /**
     * Provides test data for unique identifier fallback logic.
     *
     * @return \Generator<string, array{string, ?string, string}>
     */
    public static function uniqueIdentifierFallbackProvider(): \Generator
    {
        yield 'explicit unique identifier set' => [
            'address-123',
            'unique-456',
            'unique-456'
        ];

        yield 'no unique identifier set' => [
            'address-789',
            null,
            'address-789'
        ];

        yield 'empty unique identifier string' => [
            'address-abc',
            '',
            ''
        ];

        yield 'whitespace unique identifier' => [
            'address-def',
            '  ',
            '  '
        ];
    }

    /**
     * Tests that manually created extensions work in collections.
     *
     * This prevents the TypeError that caused production failures when
     * extensions without proper unique identifiers were added to collections.
     */
    public function testManuallyCreatedExtensionWorksInCollection(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-address-id');
        $collection = new EnderecoCustomerAddressExtensionCollection();

        $collection->add($extension);

        $this->assertCount(1, $collection);
        $this->assertSame($extension, $collection->first());
    }

    /**
     * Tests that createOrderAddressExtension produces valid entities.
     *
     * Validates that order extensions created from customer extensions
     * have proper unique identifiers and can be added to collections.
     */
    public function testCreateOrderAddressExtensionProducesValidEntity(): void
    {
        $customerExtension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('customer-id');
        $customerExtension->setAmsStatus('address_correct');
        $customerExtension->setStreet('Test Street');
        $customerExtension->setHouseNumber('123');

        $orderExtension = $customerExtension->createOrderAddressExtension('order-id');

        $this->assertNotEmpty($orderExtension->getUniqueIdentifier());

        $collection = new EnderecoOrderAddressExtensionCollection();
        $collection->add($orderExtension);
        $this->assertSame(1, $collection->count());
    }

    /**
     * Tests that getUniqueIdentifier returns the address ID.
     *
     * Validates the override mechanism that ensures extensions always
     * have a valid unique identifier for collection indexing.
     */
    public function testGetUniqueIdentifierReturnsAddressId(): void
    {
        $addressId = 'test-address-id';
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues($addressId);

        $this->assertSame($addressId, $extension->getUniqueIdentifier());
    }

    /**
     * Tests that getUniqueIdentifier never returns null.
     *
     * This is critical to prevent TypeError exceptions in EntityCollection
     * which requires string return values for unique identifiers.
     */
    public function testGetUniqueIdentifierNeverReturnsNull(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('any-id');

        $uniqueId = $extension->getUniqueIdentifier();

        $this->assertNotEmpty($uniqueId);
    }

    /**
     * Tests that getUniqueIdentifier handles conditional fallback logic correctly.
     *
     * Validates the critical fallback mechanism that ensures collection operations
     * work properly by testing unique identifier priority: custom ID first, then address ID.
     */
    #[DataProvider('uniqueIdentifierFallbackProvider')]
    public function testGetUniqueIdentifierConditionalLogic(
        string $addressId,
        ?string $uniqueIdentifierToSet,
        string $expectedReturn
    ): void {
        $extension = new EnderecoCustomerAddressExtensionEntity();
        $extension->setAddressId($addressId);

        if ($uniqueIdentifierToSet !== null) {
            $extension->setUniqueIdentifier($uniqueIdentifierToSet);
        }

        $this->assertSame($expectedReturn, $extension->getUniqueIdentifier());
    }

    /**
     * Tests that manually set unique identifiers work in collections correctly.
     *
     * Validates the complete workflow of setting custom unique identifiers and using them
     * in collections, ensuring separation between address ID and unique identifier.
     */
    public function testManuallySetUniqueIdentifierWorks(): void
    {
        $addressId = 'customer-address-123';
        $customUniqueId = 'custom-unique-456';

        $extension = new EnderecoCustomerAddressExtensionEntity();
        $extension->setAddressId($addressId);
        $extension->setUniqueIdentifier($customUniqueId);

        $this->assertSame($customUniqueId, $extension->getUniqueIdentifier());
        $this->assertSame($addressId, $extension->getAddressId());
        $this->assertNotEquals($extension->getUniqueIdentifier(), $extension->getAddressId());

        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);

        $this->assertTrue($collection->has($customUniqueId));
        $collectionSize = $collection->count();
        $this->assertSame(1, $collectionSize);
        $this->assertNotEquals($customUniqueId, $addressId);
    }

    /**
     * Tests that setAddress accepts a valid CustomerAddressEntity.
     *
     * Validates the validation logic that ensures only correct entity types
     * are accepted, preventing type confusion in the plugin.
     */
    public function testSetAddressAcceptsValidCustomerAddressEntity(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $customerAddressStub = $this->createStub(CustomerAddressEntity::class);

        $extension->setAddress($customerAddressStub);

        $this->assertSame($customerAddressStub, $extension->getAddress());
    }

    /**
     * Tests that setAddress correctly handles null values.
     *
     * Validates the validation logic allows null values to clear
     * the address association.
     */
    public function testSetAddressAcceptsNull(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $customerAddressStub = $this->createStub(CustomerAddressEntity::class);

        $extension->setAddress($customerAddressStub);
        $this->assertSame($customerAddressStub, $extension->getAddress());

        $extension->setAddress(null);
        $this->assertNull($extension->getAddress());
    }

    /**
     * Tests that setAddress throws exception for invalid entity types.
     *
     * Validates the validation logic that prevents type confusion
     * by rejecting non-CustomerAddressEntity instances.
     */
    public function testSetAddressThrowsExceptionForInvalidEntityType(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $wrongEntityTypeStub = $this->createStub(Entity::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The address must be an instance of CustomerAddressEntity.');

        $extension->setAddress($wrongEntityTypeStub);
    }

    /**
     * Tests business logic status checking methods with various AMS statuses.
     *
     * These methods contain critical business logic that determines frontend
     * behavior and address processing workflows.
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
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $extension->setAmsStatus($amsStatus);

        $this->assertSame($expectedIsChecked, $extension->isAddressChecked());
        $this->assertSame($expectedNeedsCorrection, $extension->needsCorrectionInFrontend());
        $this->assertSame($expectedHasMinorCorrection, $extension->hasMinorCorrection());
        $this->assertSame($expectedIsSelectedAutomatically, $extension->isSelectedAutomatically());
        $this->assertSame($expectedIsSelectedByCustomer, $extension->isSelectedByCustomer());
    }

    /**
     * Tests that business logic methods handle complex status strings correctly.
     *
     * Some AMS statuses might be complex strings with multiple parts,
     * ensuring the string matching logic works reliably.
     */
    public function testBusinessLogicHandlesComplexStatusStrings(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');

        $extension->setAmsStatus('address_multiple_variants_with_extra_info');
        $this->assertTrue($extension->needsCorrectionInFrontend());
        $this->assertFalse($extension->hasMinorCorrection());

        $extension->setAmsStatus('some_address_minor_correction_status');
        $this->assertTrue($extension->hasMinorCorrection());

        $extension->setAmsStatus('');
        $this->assertTrue($extension->isAddressChecked());
        $this->assertFalse($extension->needsCorrectionInFrontend());
    }

    /**
     * Tests that house number getter handles various string values correctly.
     *
     * The getHouseNumber method contains null coalescing logic that ensures
     * it never returns null values, which must be tested.
     */
    #[DataProvider('houseNumberEdgeCaseProvider')]
    public function testHouseNumberHandlesEdgeCases(string $setValue, string $expectedValue): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');

        $extension->setHouseNumber($setValue);

        $this->assertSame($expectedValue, $extension->getHouseNumber());
    }

    /**
     * Tests that sync operation preserves unique identifier.
     *
     * Ensures sync doesn't interfere with collection functionality
     * by maintaining stable unique identifiers.
     */
    public function testSyncPreservesUniqueIdentifier(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setStreet('Original Street');

        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        $extension2->setStreet('New Street');

        $originalId = $extension1->getUniqueIdentifier();

        $extension1->sync($extension2);

        $this->assertSame($originalId, $extension1->getUniqueIdentifier());
        $this->assertSame('New Street', $extension1->getStreet());
    }

    /**
     * Tests sync method with empty and null values.
     *
     * Ensures sync operation handles edge cases without corrupting
     * the entity state or causing runtime errors.
     */
    public function testSyncWithEmptyValues(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setStreet('Original Street');
        $extension1->setHouseNumber('123');
        $extension1->setAmsStatus('address_correct');

        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        // Leave extension2 with default empty values

        $extension1->sync($extension2);

        $this->assertSame('', $extension1->getStreet());
        $this->assertSame('', $extension1->getHouseNumber());
        $this->assertSame(EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED, $extension1->getAmsStatus());
        $this->assertSame(0, $extension1->getAmsTimestamp());
        $this->assertSame([], $extension1->getAmsPredictions());
        $this->assertSame('', $extension1->getAmsRequestPayload());
        $this->assertFalse($extension1->isPayPalAddress());
    }

    /**
     * Tests that sync operation copies Amazon Pay flag when present.
     *
     * Validates that the sync method properly transfers Amazon Pay payment flags
     * from source to target extension during synchronization operations.
     */
    public function testSyncCopiesAmazonPayFlag(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setIsAmazonPayAddress(false);

        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        $extension2->setIsAmazonPayAddress(true);

        $extension1->sync($extension2);

        $this->assertTrue($extension1->isAmazonPayAddress());
    }

    /**
     * Tests that sync operation copies PayPal flag when present.
     *
     * Validates that the sync method properly transfers PayPal payment flags
     * from source to target extension during synchronization operations.
     */
    public function testSyncCopiesPayPalFlag(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setIsPayPalAddress(false);

        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        $extension2->setIsPayPalAddress(true);

        $extension1->sync($extension2);

        $this->assertTrue($extension1->isPayPalAddress());
    }

    /**
     * Tests that entity is initialized with correct default values.
     *
     * Validates that newly created entities have safe default values
     * that won't cause runtime errors in business logic methods.
     */
    public function testEntityDefaultValues(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');

        $this->assertSame('test-id', $extension->getAddressId());
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

    /**
     * Tests createOrderAddressExtension preserves all data correctly.
     *
     * Validates that all address verification data is properly transferred
     * from customer extension to order extension during checkout.
     */
    public function testCreateOrderAddressExtensionPreservesAllData(): void
    {
        $customerExtension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('customer-id');
        $customerExtension->setAmsStatus('address_minor_correction');
        $customerExtension->setAmsTimestamp(1234567890);
        $customerExtension->setAmsPredictions([['field' => 'value']]);
        $customerExtension->setAmsRequestPayload('{"test": "payload"}');
        $customerExtension->setIsPayPalAddress(true);
        $customerExtension->setIsAmazonPayAddress(true);
        $customerExtension->setStreet('Test Street');
        $customerExtension->setHouseNumber('42A');

        $orderExtension = $customerExtension->createOrderAddressExtension('order-id');

        $this->assertSame('order-id', $orderExtension->getAddressId());
        $this->assertSame('address_minor_correction', $orderExtension->getAmsStatus());
        $this->assertSame(1234567890, $orderExtension->getAmsTimestamp());
        $this->assertSame([['field' => 'value']], $orderExtension->getAmsPredictions());
        $this->assertSame('{"test": "payload"}', $orderExtension->getAmsRequestPayload());
        $this->assertTrue($orderExtension->isPayPalAddress());
        $this->assertTrue($orderExtension->isAmazonPayAddress());
        $this->assertSame('Test Street', $orderExtension->getStreet());
        $this->assertSame('42A', $orderExtension->getHouseNumber());

        $this->assertNotEmpty($orderExtension->getId());
        $this->assertSame($orderExtension->getId(), $orderExtension->getUniqueIdentifier());
    }
}
