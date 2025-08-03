<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\PayPalExpressFlagIsSetInsurance;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\Struct;

#[CoversClass(PayPalExpressFlagIsSetInsurance::class)]
class PayPalExpressFlagIsSetInsuranceTest extends TestCase
{
    /** @var EntityRepository<CustomerCollection>&MockObject */
    private EntityRepository&MockObject $customerRepositoryMock;

    /** @var EntityRepository<EnderecoCustomerAddressExtensionCollection>&MockObject */
    private EntityRepository&MockObject $addressExtensionRepositoryMock;

    private PayPalExpressFlagIsSetInsurance $insurance;

    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->createMock(EntityRepository::class);
        $this->addressExtensionRepositoryMock = $this->createMock(EntityRepository::class);

        $this->insurance = new PayPalExpressFlagIsSetInsurance(
            $this->customerRepositoryMock,
            $this->addressExtensionRepositoryMock
        );
    }

    // Tests for getPriority

    /**
     * Tests that getPriority returns expected static value.
     *
     * Validates the priority ordering system for insurance services execution.
     * Lower priority values execute first, ensuring PayPal flag is set early.
     */
    public function testGetPriorityReturnsExpectedValue(): void
    {
        $priority = PayPalExpressFlagIsSetInsurance::getPriority();

        $this->assertSame(-10, $priority);
    }

    // Tests for extension validation

    /**
     * Tests that ensure throws exception when extension is missing.
     *
     * Validates the runtime safety check that ensures the address
     * extension is properly initialized before processing.
     */
    public function testEnsureThrowsExceptionWhenExtensionMissing(): void
    {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setCustomerId('customer-123');
        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure throws exception when extension has wrong type.
     *
     * Validates the type safety check that ensures the extension
     * is the correct Endereco extension type.
     */
    #[DataProvider('invalidExtensionTypesProvider')]
    public function testEnsureThrowsExceptionWhenExtensionHasWrongType(mixed $extension): void
    {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setCustomerId('customer-123');

        if ($extension !== null) {
            $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $extension);
        }

        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    // Tests for customer lookup

    /**
     * Tests that ensure throws exception when customer not found.
     *
     * Validates the error handling when the customer repository
     * cannot locate the customer associated with the address.
     */
    public function testEnsureThrowsExceptionWhenCustomerNotFound(): void
    {
        $customerId = 'non-existent-customer';
        $addressEntity = $this->createAddressEntityWithExtension($customerId);
        $context = Context::createCliContext();

        $searchResultStub = $this->createStub(EntitySearchResult::class);
        $searchResultStub->method('first')->willReturn(null);

        $this->customerRepositoryMock->expects($this->once())
            ->method('search')
            ->with($this->callback(function (Criteria $criteria) use ($customerId) {
                return $criteria->getIds() === [$customerId];
            }), $context)
            ->willReturn($searchResultStub);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Customer not found');

        $this->insurance->ensure($addressEntity, $context);
    }

    // Tests for PayPal Express flag handling

    /**
     * Tests that ensure correctly identifies and handles PayPal Express customers.
     *
     * Validates the complete workflow for customers with PayPal Express payer IDs:
     * - Customer retrieval
     * - PayPal Express flag detection
     * - Database persistence
     * - Extension entity update
     *
     * @param array<string, mixed>|null $customFields
     */
    #[DataProvider('payPalExpressPayerIdentifierProvider')]
    public function testEnsureHandlesPayPalExpressCustomers(
        ?array $customFields,
        bool $expectedFlagValue
    ): void {
        $customerId = 'customer-123';
        $addressId = 'address-456';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity($customerId, $customFields);
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => $expectedFlagValue
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertSame($expectedFlagValue, $addressExtension->isPayPalAddress());
    }

    /**
     * Tests that ensure properly sets flag to false for non-PayPal customers.
     *
     * Validates the workflow for regular customers without PayPal Express integration:
     * - Customer retrieval succeeds
     * - PayPal Express flag detection returns false
     * - Flag is properly persisted as false
     * - Extension entity is updated correctly
     */
    public function testEnsureSetsForNonPayPalCustomer(): void
    {
        $customerId = 'regular-customer-789';
        $addressId = 'address-789';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity($customerId, ['regular_field' => 'value']);
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => false
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertFalse($addressExtension->isPayPalAddress());
    }

    /**
     * Tests that ensure properly sets flag to true for existing PayPal Express customers.
     *
     * Validates the workflow for customers with PayPal Express payer IDs:
     * - Customer retrieval succeeds
     * - PayPal Express flag detection returns true
     * - Flag is properly persisted as true
     * - Extension entity is updated correctly
     */
    public function testEnsureSetsForPayPalExpressCustomer(): void
    {
        $customerId = 'paypal-customer-456';
        $addressId = 'address-456';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity(
            $customerId,
            ['payPalExpressPayerId' => 'payer-123456789']
        );
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => true
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertTrue($addressExtension->isPayPalAddress());
    }

    /**
     * Tests that ensure works correctly when isPayPalAddress is initially false.
     *
     * Validates that the insurance can handle initialized flag states
     * and properly set the value based on customer analysis.
     */
    public function testEnsureWorksWithInitiallyFalseFlag(): void
    {
        $customerId = 'customer-null-flag';
        $addressId = 'address-null-flag';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);
        $addressExtension->setIsPayPalAddress(false);

        $customer = $this->createCustomerEntity($customerId, null);
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => false
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertFalse($addressExtension->isPayPalAddress());
    }

    // Tests for repository interaction

    /**
     * Tests that ensure correctly handles repository search criteria.
     *
     * Validates that the customer repository is called with the correct
     * search criteria containing only the customer ID.
     */
    public function testEnsureUsesCorrectSearchCriteria(): void
    {
        $customerId = 'criteria-test-customer';
        $addressEntity = $this->createAddressEntityWithExtension($customerId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity($customerId, null);

        $searchResultStub = $this->createStub(EntitySearchResult::class);
        $searchResultStub->method('first')->willReturn($customer);

        $this->customerRepositoryMock->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(function (Criteria $criteria) use ($customerId) {
                    $ids = $criteria->getIds();
                    return count($ids) === 1 && $ids[0] === $customerId;
                }),
                $this->identicalTo($context)
            )
            ->willReturn($searchResultStub);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert');

        $this->insurance->ensure($addressEntity, $context);
    }

    // Tests for specific PayPal Express scenarios

    /**
     * Tests that ensure handles PayPal Express payer ID with empty string correctly.
     *
     * Validates that empty string values in the PayPal Express payer ID field
     * are treated as indicating a PayPal Express customer (as per business logic).
     */
    public function testEnsureHandlesEmptyStringPayerIdAsTrue(): void
    {
        $customerId = 'paypal-empty-string-customer';
        $addressId = 'address-empty-string';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity($customerId, ['payPalExpressPayerId' => '']);
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => true
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertTrue($addressExtension->isPayPalAddress());
    }

    /**
     * Tests that ensure handles null PayPal Express payer ID correctly.
     *
     * Validates that null values in the PayPal Express payer ID field
     * are treated as indicating a non-PayPal Express customer.
     */
    public function testEnsureHandlesNullPayerIdAsFalse(): void
    {
        $customerId = 'paypal-null-customer';
        $addressId = 'address-null';
        $addressEntity = $this->createAddressEntityWithExtension($customerId, $addressId);
        $context = Context::createCliContext();

        $customer = $this->createCustomerEntity($customerId, ['payPalExpressPayerId' => null]);
        $this->setupCustomerRepositoryMock($customerId, $customer, $context);

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $addressExtension);

        $this->addressExtensionRepositoryMock->expects($this->once())
            ->method('upsert')
            ->with(
                [
                    [
                        'addressId' => $addressId,
                        'isPayPalAddress' => false
                    ]
                ],
                $context
            );

        $this->insurance->ensure($addressEntity, $context);

        $this->assertFalse($addressExtension->isPayPalAddress());
    }

    /**
     * Provides test data for different customer custom field scenarios.
     *
     * @return Generator<string, array{array<string, mixed>|null, bool}>
     */
    public static function payPalExpressPayerIdentifierProvider(): Generator
    {
        yield 'null custom fields' => [null, false];
        yield 'empty custom fields' => [[], false];
        yield 'missing paypal express field' => [['other_field' => 'value'], false];
        yield 'paypal express field with null value' => [['payPalExpressPayerId' => null], false];
        yield 'paypal express field with empty string' => [['payPalExpressPayerId' => ''], true];
        yield 'paypal express field with valid id' => [['payPalExpressPayerId' => 'payer-123456'], true];
        yield 'paypal express field with other fields' => [
            ['payPalExpressPayerId' => 'payer-789', 'other_field' => 'value'],
            true
        ];
    }

    /**
     * Provides test data for different extension scenarios.
     *
     * @return Generator<string, array{mixed}>
     */
    public static function invalidExtensionTypesProvider(): Generator
    {
        yield 'no extension' => [null];
        yield 'wrong extension type' => [new class extends Struct {
        }];
    }

    /**
     * Creates a customer address entity with a valid Endereco extension.
     */
    private function createAddressEntityWithExtension(
        string $customerId = 'test-customer-id',
        string $addressId = 'test-address-id'
    ): CustomerAddressEntity {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId($addressId);
        $addressEntity->setCustomerId($customerId);

        $extension = new EnderecoCustomerAddressExtensionEntity();
        $extension->setAddressId($addressId);

        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $extension);

        return $addressEntity;
    }

    /**
     * Creates a customer entity with specified custom fields.
     *
     * @param array<string, mixed>|null $customFields
     */
    private function createCustomerEntity(
        string $customerId,
        ?array $customFields = null
    ): CustomerEntity {
        $customer = new CustomerEntity();
        $customer->setId($customerId);

        if ($customFields !== null) {
            $customer->setCustomFields($customFields);
        }

        return $customer;
    }

    /**
     * Sets up the customer repository mock to return the specified customer.
     */
    private function setupCustomerRepositoryMock(
        string $customerId,
        CustomerEntity $customer,
        Context $context
    ): void {
        $searchResultStub = $this->createStub(EntitySearchResult::class);
        $searchResultStub->method('first')->willReturn($customer);

        $this->customerRepositoryMock->expects($this->once())
            ->method('search')
            ->with(
                $this->callback(function (Criteria $criteria) use ($customerId) {
                    return $criteria->getIds() === [$customerId];
                }),
                $context
            )
            ->willReturn($searchResultStub);
    }
}
