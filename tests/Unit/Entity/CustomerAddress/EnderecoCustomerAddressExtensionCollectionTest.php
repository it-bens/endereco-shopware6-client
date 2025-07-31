<?php declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Entity\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Tests for EnderecoCustomerAddressExtensionCollection
 * 
 * These tests ensure the collection properly handles extensions and prevents
 * the getUniqueIdentifier TypeError that caused production issues.
 */
class EnderecoCustomerAddressExtensionCollectionTest extends TestCase
{
    /**
     * THE COLLECTION KILLER TEST - Direct validation of the original bug scenario
     * 
     * This test simulates the exact scenario that caused the TypeError:
     * manually created extension being added to EntityCollection.
     */
    public function testCollectionCanAddManuallyCreatedExtensions(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-address-id');
        
        $collection = new EnderecoCustomerAddressExtensionCollection();
        
        // This would throw TypeError: getUniqueIdentifier(): Return value must be of type string, null returned
        // if the entity doesn't properly implement getUniqueIdentifier()
        $collection->add($extension);
        
        $this->assertEquals(1, $collection->count());
        $this->assertTrue($collection->has('test-address-id'));
        $this->assertSame($extension, $collection->get('test-address-id'));
    }

    /**
     * Tests collection behavior with multiple extensions
     * 
     * Ensures multiple extensions can be processed without unique identifier conflicts.
     */
    public function testCollectionCanAddMultipleExtensions(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        
        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        
        $extension3 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-3');
        
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension1);
        $collection->add($extension2);
        $collection->add($extension3);
        
        $this->assertEquals(3, $collection->count());
        $this->assertTrue($collection->has('address-1'));
        $this->assertTrue($collection->has('address-2'));
        $this->assertTrue($collection->has('address-3'));
    }

    /**
     * Tests that collection properly handles duplicate unique identifiers
     * 
     * When two extensions have the same addressId, the second should replace the first.
     */
    public function testCollectionHandlesDuplicateUniqueIdentifiers(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('same-address-id');
        $extension1->setStreet('Lindenstraße');
        
        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('same-address-id');
        $extension2->setStreet('Schulstraße');
        
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension1);
        $collection->add($extension2);
        
        // Should only have one item (second replaces first)
        $this->assertEquals(1, $collection->count());
        $this->assertTrue($collection->has('same-address-id'));
        
        $storedExtension = $collection->get('same-address-id');
        $this->assertEquals('Schulstraße', $storedExtension->getStreet());
    }

    /**
     * Tests collection iteration doesn't cause unique identifier issues
     * 
     * Ensures collection can be safely iterated over.
     */
    public function testCollectionIterationWorksCorrectly(): void
    {
        $extensions = [];
        for ($i = 1; $i <= 5; $i++) {
            $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues("address-{$i}");
            $extensions[] = $extension;
        }
        
        $collection = new EnderecoCustomerAddressExtensionCollection($extensions);
        
        $this->assertEquals(5, $collection->count());
        
        $iteratedCount = 0;
        foreach ($collection as $key => $extension) {
            $this->assertStringStartsWith('address-', $key);
            $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
            $this->assertEquals($key, $extension->getUniqueIdentifier());
            $iteratedCount++;
        }
        
        $this->assertEquals(5, $iteratedCount);
    }

    /**
     * Tests collection filter operations work correctly
     * 
     * Ensures filtering doesn't cause unique identifier issues.
     */
    public function testCollectionFilteringWorksCorrectly(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        $extension1->setAmsStatus('address_correct');
        
        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        $extension2->setAmsStatus('not-checked');
        
        $extension3 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-3');
        $extension3->setAmsStatus('address_correct');
        
        $collection = new EnderecoCustomerAddressExtensionCollection([$extension1, $extension2, $extension3]);
        
        $filteredCollection = $collection->filter(function ($extension) {
            return $extension->getAmsStatus() === 'address_correct';
        });
        
        $this->assertEquals(2, $filteredCollection->count());
        $this->assertTrue($filteredCollection->has('address-1'));
        $this->assertTrue($filteredCollection->has('address-3'));
        $this->assertFalse($filteredCollection->has('address-2'));
    }

    /**
     * Tests collection removal operations
     * 
     * Ensures elements can be safely removed from collection.
     */
    public function testCollectionRemovalWorksCorrectly(): void
    {
        $extension1 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-1');
        
        $extension2 = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('address-2');
        
        $collection = new EnderecoCustomerAddressExtensionCollection([$extension1, $extension2]);
        
        $this->assertEquals(2, $collection->count());
        
        $collection->remove('address-1');
        
        $this->assertEquals(1, $collection->count());
        $this->assertFalse($collection->has('address-1'));
        $this->assertTrue($collection->has('address-2'));
    }

    /**
     * Tests that extension with associated CustomerAddressEntity works correctly in collection
     * 
     * Validates collection behavior when extension has full association data.
     */
    public function testCollectionWorksWithAssociatedAddressExtensions(): void
    {
        $customerAddress = new \Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity();
        $customerAddress->setId('customer-address-id');
        
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('customer-address-id');
        $extension->setAddress($customerAddress);
        
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);
        
        $this->assertEquals(1, $collection->count());
        $this->assertTrue($collection->has('customer-address-id'));
        $this->assertSame($extension, $collection->get('customer-address-id'));
        $this->assertSame($customerAddress, $extension->getAddress());
    }

    /**
     * Tests that collection returns correct API alias
     * 
     * Validates the API alias implementation.
     */
    public function testCollectionApiAlias(): void
    {
        $collection = new EnderecoCustomerAddressExtensionCollection();
        
        $this->assertEquals('endereco_customer_address_extension_collection', $collection->getApiAlias());
    }
}