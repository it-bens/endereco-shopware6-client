<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressExtensionExistsInsurance;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;

#[CoversClass(AddressExtensionExistsInsurance::class)]
class AddressExtensionExistsInsuranceTest extends TestCase
{
    protected function setUp(): void
    {
    }

    /**
     * Tests that getPriority returns expected static value.
     *
     * Validates the priority system used for ordering multiple insurance services.
     */
    public function testGetPriorityReturnsExpectedValue(): void
    {
        $priority = AddressExtensionExistsInsurance::getPriority();

        $this->assertSame(0, $priority);
    }

    /**
     * Tests that ensure creates extension when none exists.
     *
     * Validates the core business logic that creates and persists
     * new address extensions for customer addresses without them.
     */
    public function testEnsureCreatesExtensionWhenNoneExists(): void
    {
        $addressId = 'test-address-id';
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId($addressId);

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        $enderecoCustomerAddressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                $this->callback(function ($data) use ($addressId) {
                    $this->assertIsArray($data);
                    $this->assertCount(1, $data);

                    $extensionData = $data[0];
                    $this->assertArrayHasKey('addressId', $extensionData);
                    $this->assertArrayHasKey('amsStatus', $extensionData);
                    $this->assertArrayHasKey('amsPredictions', $extensionData);

                    $this->assertSame($addressId, $extensionData['addressId']);
                    $this->assertSame(
                        EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
                        $extensionData['amsStatus']
                    );
                    $this->assertSame([], $extensionData['amsPredictions']);

                    return true;
                }),
                $this->identicalTo($context)
            );

        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryMock);

        $insurance->ensure($customerAddress, $context);

        // Verify extension was added to address entity
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertSame($addressId, $extension->getAddressId());
        $this->assertSame($customerAddress, $extension->getAddress());
    }

    /**
     * Tests that ensure skips creation when valid extension exists.
     *
     * Validates the conditional logic that prevents duplicate extension
     * creation for addresses that already have valid extensions.
     */
    public function testEnsureSkipsCreationWhenValidExtensionExists(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('test-address-id');

        // Add existing valid extension
        $existingExtension = new EnderecoCustomerAddressExtensionEntity();
        $customerAddress->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $existingExtension);

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        $enderecoCustomerAddressExtensionRepositoryMock->expects($this->never())
            ->method('upsert');

        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryMock);

        $insurance->ensure($customerAddress, $context);

        // Should still have the original extension
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertSame($existingExtension, $extension);
    }

    /**
     * Tests ensure behavior with various extension scenarios.
     *
     * Validates the extension type checking logic that determines
     * whether a new extension needs to be created.
     */
    #[DataProvider('extensionScenariosProvider')]
    public function testEnsureBehaviorWithVariousExtensionTypes(mixed $existingExtension, bool $shouldCreateNew): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('test-address-id');

        if ($existingExtension !== null) {
            $customerAddress->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $existingExtension);
        }

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        if ($shouldCreateNew) {
            $enderecoCustomerAddressExtensionRepositoryMock->expects($this->once())
                ->method('upsert');
        } else {
            $enderecoCustomerAddressExtensionRepositoryMock->expects($this->never())
                ->method('upsert');
        }

        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryMock);

        $insurance->ensure($customerAddress, $context);

        // After ensure, should always have a valid extension
        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
    }

    /**
     * Tests that created extensions have proper address relationship.
     *
     * Validates the bidirectional relationship setup between
     * customer address and its extension entity.
     */
    #[DataProvider('addressIdentifierProvider')]
    public function testCreatedExtensionHasProperAddressRelationship(string $addressId): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId($addressId);

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryStub = $this->createStub(EntityRepository::class);
        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryStub);

        $insurance->ensure($customerAddress, $context);

        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertSame($addressId, $extension->getAddressId());
        $this->assertSame($customerAddress, $extension->getAddress());
    }

    /**
     * Tests that created extensions have proper unique identifiers.
     *
     * Validates that extensions created by the insurance have valid
     * unique identifiers that prevent collection processing errors.
     */
    public function testCreatedExtensionHasValidUniqueIdentifier(): void
    {
        $addressId = 'unique-test-id';
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId($addressId);

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryStub = $this->createStub(EntityRepository::class);
        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryStub);

        $insurance->ensure($customerAddress, $context);

        $extension = $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
        $this->assertSame($addressId, $extension->getUniqueIdentifier());
        $this->assertNotNull($extension->getUniqueIdentifier());
        $this->assertIsString($extension->getUniqueIdentifier());
    }

    /**
     * Tests that repository upsert is called with correct data structure.
     *
     * Validates the data format passed to the repository for persistence,
     * ensuring all required fields are present with correct values.
     */
    public function testRepositoryUpsertCalledWithCorrectDataStructure(): void
    {
        $addressId = 'struct-test-id';
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId($addressId);

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        $enderecoCustomerAddressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                $this->callback(function ($data) use ($addressId) {
                    $this->assertIsArray($data);
                    $this->assertCount(1, $data);

                    $extensionData = $data[0];

                    // Verify required keys exist
                    $requiredKeys = ['addressId', 'amsStatus', 'amsPredictions'];
                    foreach ($requiredKeys as $key) {
                        $this->assertArrayHasKey($key, $extensionData, "Missing required key: {$key}");
                    }

                    // Verify data types and values
                    $this->assertIsString($extensionData['addressId']);
                    $this->assertIsString($extensionData['amsStatus']);
                    $this->assertIsArray($extensionData['amsPredictions']);

                    $this->assertSame($addressId, $extensionData['addressId']);
                    $this->assertSame(
                        EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
                        $extensionData['amsStatus']
                    );
                    $this->assertEmpty($extensionData['amsPredictions']);

                    return true;
                }),
                $this->identicalTo($context)
            );

        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryMock);

        $insurance->ensure($customerAddress, $context);
    }

    /**
     * Tests that context is properly passed through to repository.
     *
     * Validates that the Shopware context provided to ensure()
     * is correctly passed to the repository for persistence operations.
     */
    public function testContextIsProperlyPassedToRepository(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('context-test-id');

        $customContext = Context::createCliContext();
        $customContext->addState('test-state');

        $enderecoCustomerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        $enderecoCustomerAddressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    $this->assertInstanceOf(Context::class, $context);
                    $this->assertTrue($context->hasState('test-state'));
                    return true;
                })
            );

        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryMock);

        $insurance->ensure($customerAddress, $customContext);
    }

    /**
     * Tests that extension identifier constants are used correctly.
     *
     * Validates that the correct extension identifier is used
     * when adding the extension to the customer address entity.
     */
    public function testExtensionIdentifierConstantUsedCorrectly(): void
    {
        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setId('constant-test-id');

        $context = Context::createCliContext();

        $enderecoCustomerAddressExtensionRepositoryStub = $this->createStub(EntityRepository::class);
        $insurance = new AddressExtensionExistsInsurance($enderecoCustomerAddressExtensionRepositoryStub);

        $insurance->ensure($customerAddress, $context);

        // Verify extension is stored under correct identifier
        $this->assertTrue($customerAddress->hasExtension(CustomerAddressExtension::ENDERECO_EXTENSION));
        $this->assertInstanceOf(
            EnderecoCustomerAddressExtensionEntity::class,
            $customerAddress->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION)
        );
    }

    /**
     * Provides test data for address entities with various ID formats.
     *
     * @return Generator<string, array{string}>
     */
    public static function addressIdentifierProvider(): Generator
    {
        yield 'standard uuid' => ['550e8400-e29b-41d4-a716-446655440000'];
        yield 'short id' => ['abc123'];
        yield 'complex id with special chars' => ['test-address_id.special123'];
    }

    /**
     * Provides test data for extension scenarios.
     *
     * @return Generator<string, array{mixed, bool}>
     */
    public static function extensionScenariosProvider(): Generator
    {
        yield 'no extension' => [null, true];
        yield 'wrong extension type' => [
            (function () {
                return new class extends Struct {
                };
            })(),
            true
        ];
        yield 'valid extension' => [new EnderecoCustomerAddressExtensionEntity(), false];
    }
}
