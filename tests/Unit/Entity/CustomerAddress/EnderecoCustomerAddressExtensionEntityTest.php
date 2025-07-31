<?php declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Entity\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

/**
 * Tests for EnderecoCustomerAddressExtensionEntity
 * 
 * These tests are critical for preventing the getUniqueIdentifier TypeError
 * that caused customer outages in production.
 */
class EnderecoCustomerAddressExtensionEntityTest extends TestCase
{
    /**
     * THE KILLER TEST - This would have caught the original getUniqueIdentifier bug
     * 
     * Ensures manually created extensions can be added to collections without TypeError.
     * This test prevents the exact issue that caused production failures.
     */
    public function testManuallyCreatedExtensionCanBeAddedToCollection(): void
    {
        // Create extension manually (like the insurance does)
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-address-id');
        
        // This line would throw TypeError if getUniqueIdentifier() returns null
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);
        
        $this->assertEquals(1, $collection->count());
        $this->assertSame($extension, $collection->first());
    }

    /**
     * Direct validation that getUniqueIdentifier returns the addressId
     * 
     * This ensures the override in EnderecoCustomerAddressExtensionEntity works correctly.
     */
    public function testGetUniqueIdentifierReturnsAddressId(): void
    {
        $addressId = 'test-address-id';
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues($addressId);
        
        $this->assertEquals($addressId, $extension->getUniqueIdentifier());
    }

    /**
     * Critical safety test - ensures getUniqueIdentifier never returns null
     * 
     * This prevents the TypeError that was the root cause of the production issue.
     */
    public function testGetUniqueIdentifierNeverReturnsNull(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('any-id');
        
        $uniqueId = $extension->getUniqueIdentifier();
        
        $this->assertNotNull($uniqueId);
        $this->assertIsString($uniqueId);
        $this->assertNotEmpty($uniqueId);
    }

    /**
     * Tests the createOrderAddressExtension method creates entity with proper unique identifier
     * 
     * This method is called during order conversion and must create valid entities.
     */
    public function testCreateOrderAddressExtensionHasUniqueIdentifier(): void
    {
        $customerExtension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('customer-address-id');
        $customerExtension->setAmsStatus('address_correct');
        $customerExtension->setStreet('Lindenstraße');
        $customerExtension->setHouseNumber('2');
        
        $orderExtension = $customerExtension->createOrderAddressExtension('order-address-id');
        
        // The created order extension must have a unique identifier
        $this->assertNotNull($orderExtension->getUniqueIdentifier());
        $this->assertIsString($orderExtension->getUniqueIdentifier());
        $this->assertNotEmpty($orderExtension->getUniqueIdentifier());
        
        // It should be addable to a collection without errors
        $collection = new \Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionCollection();
        $collection->add($orderExtension);
        
        $this->assertEquals(1, $collection->count());
    }

    /**
     * Tests that sync operation doesn't break unique identifier functionality
     * 
     * Ensures sync method doesn't interfere with collection processing.
     */
    public function testSyncDoesNotAffectUniqueIdentifier(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setStreet('Lindenstraße');
        
        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        $extension2->setStreet('Lindenstr.');
        
        $originalUniqueId = $extension1->getUniqueIdentifier();
        
        // Sync should not affect unique identifier
        $extension1->sync($extension2);
        
        $this->assertEquals($originalUniqueId, $extension1->getUniqueIdentifier());
        $this->assertEquals('Lindenstr.', $extension1->getStreet());
        
        // Extension should still work in collections
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension1);
        
        $this->assertEquals(1, $collection->count());
    }

    /**
     * Tests that extension can be created with unique identifier different from address ID
     * 
     * This validates that manually created extensions can have custom unique identifiers
     * separate from their address ID when not using the factory method.
     */
    public function testCanCreateExtensionWithDifferentUniqueIdFromAddressId(): void
    {
        $addressId = 'customer-address-123';
        $customUniqueId = 'custom-unique-identifier-456';
        
        // Create extension manually without factory
        $extension = new EnderecoCustomerAddressExtensionEntity();
        $extension->setAddressId($addressId);  
        $extension->setUniqueIdentifier($customUniqueId);
        
        // Verify the unique identifier is different from address ID
        $this->assertEquals($customUniqueId, $extension->getUniqueIdentifier());
        $this->assertEquals($addressId, $extension->getAddressId());
        $this->assertNotEquals($extension->getUniqueIdentifier(), $extension->getAddressId());
        
        // Ensure it still works in collections
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);
        
        $this->assertEquals(1, $collection->count());
        $this->assertTrue($collection->has($customUniqueId));
        $this->assertFalse($collection->has($addressId));
        $this->assertSame($extension, $collection->get($customUniqueId));
    }
}