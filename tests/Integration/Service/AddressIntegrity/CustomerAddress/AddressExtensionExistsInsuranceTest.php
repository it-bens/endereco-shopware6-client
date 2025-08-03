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

/**
 * Integration tests for AddressExtensionExistsInsurance
 *
 * These tests ensure the insurance properly creates extensions that can be
 * processed by Shopware's collection system without getUniqueIdentifier errors.
 */
class AddressExtensionExistsInsuranceTest extends TestCase
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
     * THE INTEGRATION KILLER TEST - This would have caught the getUniqueIdentifier bug
     *
     * Tests that the insurance creates extensions that can be processed by collections
     * without throwing TypeError. This is the critical integration test.
     */
    public function testEnsureCreatesValidExtensionThatCanBeProcessedByCollections(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('customer-address-id');

        // Mock repository to avoid database dependency
        $this->mockRepository
            ->expects($this->once())
            ->method('upsert')
            ->with(
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertCount(1, $data);
                    $this->assertEquals('customer-address-id', $data[0]['addressId']);
                    return true;
                }),
                $this->context
            );

        // This calls createAddressExtensionWithDefaultValues internally
        $this->insurance->ensure($customerAddress, $this->context);

        // Get the extension that was added to the customer address
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertEquals('customer-address-id', $extension->getAddressId());

        // THE CRITICAL TEST: Extension must be processable by collections
        $collection = new EnderecoCustomerAddressExtensionCollection();
        // This would throw TypeError if getUniqueIdentifier() returns null
        $collection->add($extension);

        $this->assertCount(1, $collection);
        $this->assertTrue($collection->has('customer-address-id'));
    }

    /**
     * Tests that createAddressExtensionWithDefaultValues creates valid extension
     *
     * This tests the factory method directly to ensure it creates properly configured entities.
     */
    public function testCreateAddressExtensionWithDefaultValuesHasUniqueIdentifier(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('test-address-id');

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->insurance);
        $method = $reflection->getMethod('createAddressExtensionWithDefaultValues');

        $extension = $method->invoke($this->insurance, $customerAddress);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertEquals('test-address-id', $extension->getAddressId());
        $this->assertEquals('test-address-id', $extension->getUniqueIdentifier());
        $this->assertSame($customerAddress, $extension->getAddress());

        // Verify it can be added to collection
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension);

        $this->assertCount(1, $collection);
    }

    /**
     * Tests that ensure doesn't create extension if one already exists
     *
     * Validates the conditional creation logic.
     */
    public function testEnsureDoesNotCreateExtensionIfAlreadyExists(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('customer-address-id');

        // Add existing extension
        $existingExtension = new EnderecoCustomerAddressExtensionEntity();
        $existingExtension->setAddressId('customer-address-id');
        $customerAddress->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $existingExtension);

        // Repository should not be called
        $this->mockRepository
            ->expects($this->never())
            ->method('upsert');

        $this->insurance->ensure($customerAddress, $this->context);

        // Should still have the original extension
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertSame($existingExtension, $extension);
    }

    /**
     * Tests that extension survives complete collection processing lifecycle
     *
     * This simulates the real-world scenario where extensions are processed through
     * various collection operations that caused the original bug.
     */
    public function testExtensionSurvivesCollectionProcessing(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('lifecycle-test-id');

        $this->mockRepository
            ->expects($this->once())
            ->method('upsert');

        // Create extension through insurance
        $this->insurance->ensure($customerAddress, $this->context);
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        // Test various collection operations that could trigger getUniqueIdentifier
        $collection = new EnderecoCustomerAddressExtensionCollection();

        // Add to collection - this was the original source of the TypeError
        if ($extension instanceof EnderecoCustomerAddressExtensionEntity) {
            $collection->add($extension);
        }
        $this->assertCount(1, $collection);

        // Iterate over collection
        foreach ($collection as $key => $item) {
            $this->assertEquals('lifecycle-test-id', $key);
            $this->assertSame($extension, $item);
        }

        // Filter collection
        $filtered = $collection->filter(function ($ext) {
            return $ext->getAmsStatus() === 'not-checked';
        });
        $this->assertCount(1, $filtered);

        // Access by key
        $retrieved = $collection->get('lifecycle-test-id');
        $this->assertSame($extension, $retrieved);

        // Test has() method
        $this->assertTrue($collection->has('lifecycle-test-id'));
    }

    /**
     * Tests that multiple addresses can have extensions created
     *
     * Validates behavior with multiple customer addresses.
     */
    public function testEnsureWorksForMultipleAddresses(): void
    {
        $address1 = new CustomerAddressEntity();
        $address1->setId('address-1');

        $address2 = new CustomerAddressEntity();
        $address2->setId('address-2');

        $this->mockRepository
            ->expects($this->exactly(2))
            ->method('upsert');

        $this->insurance->ensure($address1, $this->context);
        $this->insurance->ensure($address2, $this->context);

        $extension1 = $address1->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $extension2 = $address2->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension1);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension2);

        // Both should work in the same collection
        $collection = new EnderecoCustomerAddressExtensionCollection();
        $collection->add($extension1);
        $collection->add($extension2);

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->has('address-1'));
        $this->assertTrue($collection->has('address-2'));
    }

    /**
     * Tests that created extensions have proper default values
     *
     * Validates the default state of created extensions.
     */
    public function testCreatedExtensionHasProperDefaultValues(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('defaults-test-id');

        $this->mockRepository
            ->expects($this->once())
            ->method('upsert')
            ->with(
                $this->callback(function ($data) {
                    $extensionData = $data[0];
                    $this->assertEquals('defaults-test-id', $extensionData['addressId']);
                    $this->assertEquals('not-checked', $extensionData['amsStatus']);
                    $this->assertEquals([], $extensionData['amsPredictions']);
                    return true;
                }),
                $this->context
            );

        $this->insurance->ensure($customerAddress, $this->context);

        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertEquals('defaults-test-id', $extension->getAddressId());
        $this->assertEquals('not-checked', $extension->getAmsStatus());
        $this->assertEquals([], $extension->getAmsPredictions());
        $this->assertSame($customerAddress, $extension->getAddress());
    }
}
