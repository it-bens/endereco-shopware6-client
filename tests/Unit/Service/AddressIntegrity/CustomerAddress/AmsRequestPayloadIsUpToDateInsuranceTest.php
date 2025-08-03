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
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsRequestPayloadIsUpToDateInsurance;
use Endereco\Shopware6Client\Service\EnderecoService;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;

#[CoversClass(AmsRequestPayloadIsUpToDateInsurance::class)]
class AmsRequestPayloadIsUpToDateInsuranceTest extends TestCase
{
    private IsAmsRequestPayloadIsUpToDateCheckerInterface&MockObject $isAmsRequestPayloadIsUpToDateCheckerMock;
    private EnderecoService&MockObject $enderecoServiceMock;
    private AmsRequestPayloadIsUpToDateInsurance $insurance;

    protected function setUp(): void
    {
        $this->isAmsRequestPayloadIsUpToDateCheckerMock = $this->createMock(
            IsAmsRequestPayloadIsUpToDateCheckerInterface::class
        );
        $this->enderecoServiceMock = $this->createMock(EnderecoService::class);

        $this->insurance = new AmsRequestPayloadIsUpToDateInsurance(
            $this->isAmsRequestPayloadIsUpToDateCheckerMock,
            $this->enderecoServiceMock
        );
    }

    /**
     * Tests that getPriority returns expected static value.
     *
     * Validates the priority system used for ordering multiple insurance services.
     * This specific insurance should run early in the chain with negative priority.
     */
    public function testGetPriorityReturnsExpectedValue(): void
    {
        $priority = AmsRequestPayloadIsUpToDateInsurance::getPriority();

        $this->assertSame(-15, $priority);
    }

    /**
     * Tests that ensure validates extension exists and is correct type.
     *
     * Validates the critical validation logic that ensures the address has
     * a valid Endereco extension before proceeding with business logic.
     * This prevents runtime errors and ensures data integrity.
     */
    #[DataProvider('invalidExtensionTypesProvider')]
    public function testEnsureThrowsExceptionWhenExtensionInvalid(mixed $invalidExtension): void
    {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId('test-address-id');

        if ($invalidExtension !== null) {
            $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $invalidExtension);
        }

        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure processes valid extension correctly.
     *
     * Validates the core business logic flow when a valid extension exists.
     * The method should check if the payload is up to date and conditionally
     * reset metadata based on the checker result.
     */
    #[DataProvider('payloadUpToDateScenariosProvider')]
    public function testEnsureProcessesValidExtensionCorrectly(bool $isPayloadUpToDate, bool $shouldResetMeta): void
    {
        $addressId = 'test-address-id';
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId($addressId);

        $addressExtension = new EnderecoCustomerAddressExtensionEntity();
        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $addressExtension);

        $context = Context::createCliContext();

        $this->isAmsRequestPayloadIsUpToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->with(
                $this->identicalTo($addressEntity),
                $this->identicalTo($addressExtension),
                $this->identicalTo($context)
            )
            ->willReturn($isPayloadUpToDate);

        if ($shouldResetMeta) {
            $this->enderecoServiceMock->expects($this->once())
                ->method('resetCustomerAddressMetaData')
                ->with(
                    $this->identicalTo($addressEntity),
                    $this->identicalTo($context)
                );
        } else {
            $this->enderecoServiceMock->expects($this->never())
                ->method('resetCustomerAddressMetaData');
        }

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure passes correct parameters to checker service.
     *
     * Validates the parameter passing behavior to ensure the checker service
     * receives exactly the expected address entity, extension, and context.
     * This ensures proper data flow through the business logic.
     */
    public function testEnsurePassesCorrectParametersToChecker(): void
    {
        $addressId = 'param-test-id';
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId($addressId);

        $addressExtension = new EnderecoCustomerAddressExtensionEntity();
        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $addressExtension);

        $customContext = Context::createCliContext();
        $customContext->addState('test-state');

        $this->isAmsRequestPayloadIsUpToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->with(
                $this->callback(function ($entity) use ($addressId) {
                    $this->assertInstanceOf(CustomerAddressEntity::class, $entity);
                    $this->assertSame($addressId, $entity->getId());
                    return true;
                }),
                $this->callback(function ($extension) {
                    $this->assertInstanceOf(EnderecoCustomerAddressExtensionEntity::class, $extension);
                    return true;
                }),
                $this->callback(function ($context) {
                    $this->assertInstanceOf(Context::class, $context);
                    $this->assertTrue($context->hasState('test-state'));
                    return true;
                })
            )
            ->willReturn(true);

        $this->insurance->ensure($addressEntity, $customContext);
    }

    /**
     * Tests that ensure passes correct parameters to reset service.
     *
     * Validates the parameter passing behavior when metadata reset is required.
     * Ensures the EnderecoService receives the correct address entity and context
     * for proper metadata reset operations.
     */
    public function testEnsurePassesCorrectParametersToResetService(): void
    {
        $addressId = 'reset-test-id';
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId($addressId);

        $addressExtension = new EnderecoCustomerAddressExtensionEntity();
        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $addressExtension);

        $customContext = Context::createCliContext();
        $customContext->addState('reset-state');

        $this->isAmsRequestPayloadIsUpToDateCheckerMock->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('resetCustomerAddressMetaData')
            ->with(
                $this->callback(function ($entity) use ($addressId) {
                    $this->assertInstanceOf(CustomerAddressEntity::class, $entity);
                    $this->assertSame($addressId, $entity->getId());
                    return true;
                }),
                $this->callback(function ($context) {
                    $this->assertInstanceOf(Context::class, $context);
                    $this->assertTrue($context->hasState('reset-state'));
                    return true;
                })
            );

        $this->insurance->ensure($addressEntity, $customContext);
    }

    /**
     * Provides test data for payload up-to-date scenarios.
     *
     * @return Generator<string, array{bool, bool}>
     */
    public static function payloadUpToDateScenariosProvider(): Generator
    {
        yield 'payload is up to date' => [true, false];
        yield 'payload is outdated' => [false, true];
    }

    /**
     * Provides test data for various extension types that should cause exceptions.
     *
     * @return Generator<string, array{mixed}>
     */
    public static function invalidExtensionTypesProvider(): Generator
    {
        yield 'no extension' => [null];
        yield 'wrong extension type' => [
            (function () {
                return new class extends Struct {
                };
            })()
        ];
    }
}
