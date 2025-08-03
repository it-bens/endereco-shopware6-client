<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Integration\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressExtensionExistsInsurance;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;

final class AddressExtensionExistsInsuranceTest extends TestCase
{
    private AddressExtensionExistsInsurance $insurance;
    /** @var EntityRepository<EnderecoCustomerAddressExtensionCollection>&MockObject */
    private EntityRepository $mockRepository;
    private Context $context;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(EntityRepository::class);
        $this->insurance = new AddressExtensionExistsInsurance($this->mockRepository);
        $this->context = Context::createCliContext();
    }

    /**
     * Tests that extensions created by the service are compatible with Shopware collections.
     *
     * This is the critical integration test that prevents TypeError exceptions
     * when extensions are processed by Shopware's collection system.
     */
    public function testEnsureCreatesCollectionCompatibleExtension(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('integration-test-id');

        $this->mockRepository
            ->expects($this->once())
            ->method('upsert')
            ->with(
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertCount(1, $data);
                    $this->assertEquals('integration-test-id', $data[0]['addressId']);
                    return true;
                }),
                $this->context
            );

        // Create extension through service
        $this->insurance->ensure($customerAddress, $this->context);

        // Get the created extension
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);

        // CRITICAL INTEGRATION TEST: Extension must work with Shopware collections
        $collection = new EnderecoCustomerAddressExtensionCollection();
        
        // This was the source of the original TypeError - test it works
        $collection->add($extension);

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->has('integration-test-id'));
        $this->assertSame($extension, $collection->get('integration-test-id'));
    }

    /**
     * Tests that the service works correctly when an extension already exists.
     *
     * Validates the integration scenario where addresses already have extensions
     * and ensures the existing extension remains collection-compatible.
     */
    public function testEnsurePreservesExistingCollectionCompatibleExtension(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('existing-extension-id');

        // Create and add existing extension
        $existingExtension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValuesFromAddress($customerAddress);
        $customerAddress->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $existingExtension);

        // Repository should not be called since extension exists
        $this->mockRepository
            ->expects($this->never())
            ->method('upsert');

        $this->insurance->ensure($customerAddress, $this->context);

        // Should still have the original extension
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertSame($existingExtension, $extension);

        // Verify existing extension still works with collections
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->has('existing-extension-id'));
    }

    /**
     * Tests integration scenario with multiple addresses and extensions.
     *
     * Validates that multiple extensions created by the service can coexist
     * in the same collection without conflicts.
     */
    public function testMultipleExtensionsWorkInSameCollection(): void
    {
        $address1 = new CustomerAddressEntity();
        $address1->setId('multi-address-1');
        
        $address2 = new CustomerAddressEntity();
        $address2->setId('multi-address-2');

        $this->mockRepository
            ->expects($this->exactly(2))
            ->method('upsert');

        // Create extensions for both addresses
        $this->insurance->ensure($address1, $this->context);
        $this->insurance->ensure($address2, $this->context);

        $extension1 = $address1->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $extension2 = $address2->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        // Both extensions should work in the same collection
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension1);
        $collection->add($extension2);

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->has('multi-address-1'));
        $this->assertTrue($collection->has('multi-address-2'));
        $this->assertSame($extension1, $collection->get('multi-address-1'));
        $this->assertSame($extension2, $collection->get('multi-address-2'));
    }

    /**
     * Tests that extensions work with collection iteration patterns.
     *
     * Validates compatibility with common collection usage patterns that
     * rely on getUniqueIdentifier() working correctly.
     */
    public function testExtensionWorksWithCollectionIteration(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('iteration-test-id');

        $this->mockRepository
            ->expects($this->once())
            ->method('upsert');

        $this->insurance->ensure($customerAddress, $this->context);
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);

        // Test iteration - this internally uses getUniqueIdentifier()
        $iteratedItems = [];
        foreach ($collection as $key => $item) {
            $iteratedItems[$key] = $item;
        }

        $this->assertCount(1, $iteratedItems);
        $this->assertArrayHasKey('iteration-test-id', $iteratedItems);
        $this->assertSame($extension, $iteratedItems['iteration-test-id']);
    }

    /**
     * Tests collection filtering functionality with created extensions.
     *
     * Validates that extensions work correctly with collection filter operations
     * that are commonly used in business logic.
     */
    public function testExtensionWorksWithCollectionFiltering(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('filter-test-id');

        $this->mockRepository
            ->expects($this->once())
            ->method('upsert');

        $this->insurance->ensure($customerAddress, $this->context);
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);

        // Test filtering - should work without errors
        $notCheckedExtensions = $collection->filter(function ($ext) {
            return $ext->getAmsStatus() === 'not-checked';
        });

        $this->assertCount(1, $notCheckedExtensions);
        
        $checkedExtensions = $collection->filter(function ($ext) {
            return $ext->getAmsStatus() === 'checked';
        });

        $this->assertCount(0, $checkedExtensions);
    }
}
