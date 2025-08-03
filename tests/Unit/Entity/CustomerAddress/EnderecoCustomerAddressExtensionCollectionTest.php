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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(EnderecoCustomerAddressExtensionCollection::class)]
class EnderecoCustomerAddressExtensionCollectionTest extends TestCase
{
    /**
     * Tests that collection returns the correct API alias.
     *
     * The API alias is used by Shopware's serialization layer
     * and must match the expected plugin-specific format.
     */
    public function testGetApiAliasReturnsExpectedValue(): void
    {
        $collection = new EnderecoCustomerAddressExtensionCollection();

        $this->assertSame('endereco_customer_address_extension_collection', $collection->getApiAlias());
    }

    /**
     * Tests that collection expects the correct entity class.
     *
     * This validates the type checking contract - ensures that only
     * EnderecoCustomerAddressExtensionEntity instances can be added
     * to this collection, preventing runtime type errors.
     */
    public function testGetExpectedClassReturnsCorrectEntityClass(): void
    {
        $collection = new EnderecoCustomerAddressExtensionCollection();

        // Use reflection to test protected method
        $reflection = new ReflectionClass($collection);
        $method = $reflection->getMethod('getExpectedClass');

        $this->assertSame(EnderecoCustomerAddressExtensionEntity::class, $method->invoke($collection));
    }

    /**
     * Tests that collection enforces type safety through getExpectedClass().
     *
     * This integration test verifies that the type checking system
     * works correctly when adding entities to the collection.
     */
    public function testTypeCheckingWorksWithCorrectEntityType(): void
    {
        $validEntity = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $collection = new EnderecoCustomerAddressExtensionCollection();

        // Should not throw an exception
        $collection->add($validEntity);

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->has('test-id'));
    }
}
