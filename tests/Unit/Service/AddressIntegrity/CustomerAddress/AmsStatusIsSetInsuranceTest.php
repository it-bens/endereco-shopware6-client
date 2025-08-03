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
use Endereco\Shopware6Client\Model\AddressCheckResult;
use Endereco\Shopware6Client\Model\FailedAddressCheckResult;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsStatusIsSetInsurance;
use Endereco\Shopware6Client\Service\EnderecoService;
use Endereco\Shopware6Client\Service\ProcessContextService;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;

#[CoversClass(AmsStatusIsSetInsurance::class)]
class AmsStatusIsSetInsuranceTest extends TestCase
{
    private IsAmsRequestPayloadIsUpToDateCheckerInterface&MockObject $upToDateCheckerMock;
    private AddressCheckerInterface&MockObject $addressCheckerMock;
    private EnderecoService&MockObject $enderecoServiceMock;
    private ProcessContextService&MockObject $processContextServiceMock;
    private AmsStatusIsSetInsurance $insurance;

    protected function setUp(): void
    {
        $this->upToDateCheckerMock = $this->createMock(IsAmsRequestPayloadIsUpToDateCheckerInterface::class);
        $this->addressCheckerMock = $this->createMock(AddressCheckerInterface::class);
        $this->enderecoServiceMock = $this->createMock(EnderecoService::class);
        $this->processContextServiceMock = $this->createMock(ProcessContextService::class);

        $this->insurance = new AmsStatusIsSetInsurance(
            $this->upToDateCheckerMock,
            $this->addressCheckerMock,
            $this->enderecoServiceMock,
            $this->processContextServiceMock
        );
    }

    /**
     * Tests that getPriority returns expected static value.
     *
     * Validates the priority ordering system for insurance services execution.
     */
    public function testGetPriorityReturnsExpectedValue(): void
    {
        $priority = AmsStatusIsSetInsurance::getPriority();

        $this->assertSame(-20, $priority);
    }

    /**
     * Tests that isValidationNeeded correctly identifies when validation is required.
     *
     * Validates the business logic that determines when an address needs
     * to undergo AMS status validation based on its current status.
     */
    #[DataProvider('amsStatusValidationProvider')]
    public function testIsValidationNeededBasedOnAmsStatus(?string $amsStatus, bool $expected): void
    {
        $extension = new EnderecoCustomerAddressExtensionEntity();
        if ($amsStatus !== null) {
            $extension->setAmsStatus($amsStatus);
        }

        $result = $this->insurance->isValidationNeeded($extension);

        $this->assertSame($expected, $result);
    }

    /**
     * Tests that ensure throws exception when extension is missing.
     *
     * Validates the runtime safety check that ensures the address
     * extension is properly initialized before processing.
     */
    public function testEnsureThrowsExceptionWhenExtensionMissing(): void
    {
        $addressEntity = new CustomerAddressEntity();
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

        if ($extension !== null) {
            $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $extension);
        }

        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure returns early when no sales channel is available.
     *
     * Validates the conditional logic that skips validation when
     * the system cannot determine the current sales channel.
     */
    public function testEnsureReturnsEarlyWhenNoSalesChannelAvailable(): void
    {
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->with($context)
            ->willReturn(null);

        $this->enderecoServiceMock->expects($this->never())
            ->method('isEnderecoPluginActive');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure returns early when Endereco plugin is inactive.
     *
     * Validates the conditional logic that skips validation when
     * the Endereco plugin is disabled for the current sales channel.
     */
    public function testEnsureReturnsEarlyWhenPluginInactive(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->with($context)
            ->willReturn($salesChannelId);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isEnderecoPluginActive')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure returns early when validation is not needed.
     *
     * Validates the conditional logic that skips validation when
     * the address extension already has a valid AMS status.
     */
    public function testEnsureReturnsEarlyWhenValidationNotNeeded(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = $this->createAddressEntityWithExtension(
            EnderecoCustomerAddressExtensionEntity::AMS_STATUS_SELECTED_BY_CUSTOMER
        );
        $context = Context::createCliContext();

        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->with($context)
            ->willReturn($salesChannelId);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isEnderecoPluginActive')
            ->with($salesChannelId)
            ->willReturn(true);

        $this->addressCheckerMock->expects($this->never())
            ->method('checkAddress');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure returns early when validation is not allowed.
     *
     * Validates the conditional logic that skips validation when
     * the address doesn't meet the criteria for validation.
     */
    public function testEnsureReturnsEarlyWhenValidationNotAllowed(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->with($context)
            ->willReturn($salesChannelId);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isEnderecoPluginActive')
            ->with($salesChannelId)
            ->willReturn(true);

        // Setup validation not allowed scenario
        $this->setupValidationNotAllowed($salesChannelId);

        $this->addressCheckerMock->expects($this->never())
            ->method('checkAddress');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure returns early when address check fails.
     *
     * Validates the error handling logic that gracefully handles
     * address validation failures without throwing exceptions.
     */
    public function testEnsureReturnsEarlyWhenAddressCheckFails(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $failedResultStub = $this->createStub(FailedAddressCheckResult::class);

        $this->addressCheckerMock->expects($this->once())
            ->method('checkAddress')
            ->with($addressEntity, $context, $salesChannelId, '')
            ->willReturn($failedResultStub);

        $this->enderecoServiceMock->expects($this->never())
            ->method('applyAddressCheckResult');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure throws exception when max validation attempts exceeded.
     *
     * Validates the safety mechanism that prevents infinite loops
     * when address validation consistently fails to converge.
     */
    public function testEnsureThrowsExceptionWhenMaxAttemptsExceeded(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $successfulResult = $this->createSuccessfulAddressCheckResult('session-123');

        $this->addressCheckerMock->expects($this->exactly(2))
            ->method('checkAddress')
            ->willReturn($successfulResult);

        $this->enderecoServiceMock->expects($this->exactly(2))
            ->method('applyAddressCheckResult')
            ->with($successfulResult, $addressEntity, $context);

        $this->upToDateCheckerMock->expects($this->exactly(2))
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Address validation exceeded maximum attempts (3)');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure completes successfully on first attempt.
     *
     * Validates the happy path where address validation succeeds
     * immediately and updates the address appropriately.
     */
    public function testEnsureCompletesSuccessfullyOnFirstAttempt(): void
    {
        $salesChannelId = 'test-sales-channel';
        $sessionId = 'session-456';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $successfulResult = $this->createSuccessfulAddressCheckResult($sessionId);

        $this->addressCheckerMock->expects($this->once())
            ->method('checkAddress')
            ->with($addressEntity, $context, $salesChannelId, '')
            ->willReturn($successfulResult);

        $this->enderecoServiceMock->expects($this->once())
            ->method('applyAddressCheckResult')
            ->with($successfulResult, $addressEntity, $context);

        $this->upToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->never())
            ->method('addAccountableSessionIdsToStorage');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure retries validation until payload is up to date.
     *
     * Validates the retry mechanism that continues validation attempts
     * until the address metadata converges to a stable state.
     */
    public function testEnsureRetriesValidationUntilPayloadUpToDate(): void
    {
        $salesChannelId = 'test-sales-channel';
        $sessionId = 'session-789';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $successfulResult = $this->createSuccessfulAddressCheckResult($sessionId);

        $this->addressCheckerMock->expects($this->exactly(2))
            ->method('checkAddress')
            ->willReturnCallback(function (
                $addressEntity,
                $context,
                $salesChannelId,
                $sessionId
            ) use ($successfulResult) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertSame('', $sessionId);
                } else {
                    $this->assertSame('session-789', $sessionId);
                }
                return $successfulResult;
            });

        $this->enderecoServiceMock->expects($this->exactly(2))
            ->method('applyAddressCheckResult')
            ->with($successfulResult, $addressEntity, $context);

        $this->upToDateCheckerMock->expects($this->exactly(2))
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure accounts session for billing during import process.
     *
     * Validates the billing logic that tracks validation sessions
     * when addresses are validated during import operations.
     */
    public function testEnsureAccountsSessionForBillingDuringImportProcess(): void
    {
        $salesChannelId = 'test-sales-channel';
        $sessionId = 'import-session-123';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $this->enderecoServiceMock->isImport = true;

        $successfulResult = $this->createSuccessfulAddressCheckResult($sessionId);

        $this->addressCheckerMock->expects($this->once())
            ->method('checkAddress')
            ->willReturn($successfulResult);

        $this->enderecoServiceMock->expects($this->once())
            ->method('applyAddressCheckResult');

        $this->upToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('addAccountableSessionIdsToStorage')
            ->with([$sessionId]);

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure accounts session for billing when address selected automatically.
     *
     * Validates the billing logic that tracks validation sessions
     * when addresses are automatically selected during validation.
     */
    public function testEnsureAccountsSessionForBillingWhenAddressSelectedAutomatically(): void
    {
        $salesChannelId = 'test-sales-channel';
        $sessionId = 'auto-session-456';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $this->enderecoServiceMock->isImport = false;

        $successfulResult = $this->createSuccessfulAddressCheckResult($sessionId);

        $this->addressCheckerMock->expects($this->once())
            ->method('checkAddress')
            ->willReturn($successfulResult);

        $this->enderecoServiceMock->expects($this->once())
            ->method('applyAddressCheckResult')
            ->with($successfulResult, $addressEntity, $context)
            ->willReturnCallback(function () use ($addressEntity) {
                $extension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
                if ($extension instanceof EnderecoCustomerAddressExtensionEntity) {
                    $extension->setAmsStatus('address_selected_automatically_some_suffix');
                }
            });

        $this->upToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('addAccountableSessionIdsToStorage')
            ->with([$sessionId]);

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure does not account session when conditions not met.
     *
     * Validates the billing logic that prevents tracking validation sessions
     * when they should not be counted for billing purposes.
     */
    public function testEnsureDoesNotAccountSessionWhenConditionsNotMet(): void
    {
        $salesChannelId = 'test-sales-channel';
        $sessionId = 'no-billing-session';
        $addressEntity = $this->createAddressEntityWithExtension();
        $context = Context::createCliContext();

        $this->setupValidContext($salesChannelId);
        $this->setupValidationAllowed($salesChannelId);

        $this->enderecoServiceMock->isImport = false;

        $successfulResult = $this->createSuccessfulAddressCheckResult($sessionId);

        $this->addressCheckerMock->expects($this->once())
            ->method('checkAddress')
            ->willReturn($successfulResult);

        $this->enderecoServiceMock->expects($this->once())
            ->method('applyAddressCheckResult')
            ->with($successfulResult, $addressEntity, $context)
            ->willReturnCallback(function () use ($addressEntity) {
                $extension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
                if ($extension instanceof EnderecoCustomerAddressExtensionEntity) {
                    $extension->setAmsStatus('some_other_status');
                }
            });

        $this->upToDateCheckerMock->expects($this->once())
            ->method('checkIfCustomerAddressMetaIsUpToDate')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->never())
            ->method('addAccountableSessionIdsToStorage');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that canValidate correctly identifies when existing customer check is relevant.
     *
     * Validates the business rules that determine when existing customer
     * addresses should undergo validation checks.
     */
    public function testCanValidateForExistingCustomerCheck(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = new CustomerAddressEntity();

        $this->enderecoServiceMock->expects($this->once())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isAddressFromRemote')
            ->with($addressEntity)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isAddressRecent')
            ->with($addressEntity)
            ->willReturn(false);

        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->insurance);
        $method = $reflection->getMethod('canValidate');

        $result = $method->invoke($this->insurance, $addressEntity, $salesChannelId);

        $this->assertTrue($result);
    }

    /**
     * Tests that canValidate correctly identifies when PayPal checkout check is relevant.
     *
     * Validates the business rules that determine when PayPal addresses
     * should undergo validation checks.
     */
    public function testCanValidateForPayPalCheckoutCheck(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = new CustomerAddressEntity();

        $this->enderecoServiceMock->expects($this->once())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isAddressFromPayPal')
            ->with($addressEntity)
            ->willReturn(true);

        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->insurance);
        $method = $reflection->getMethod('canValidate');

        $result = $method->invoke($this->insurance, $addressEntity, $salesChannelId);

        $this->assertTrue($result);
    }

    /**
     * Tests that canValidate correctly identifies when import file check is relevant.
     *
     * Validates the business rules that determine when imported addresses
     * should undergo validation checks.
     */
    public function testCanValidateForImportFileCheck(): void
    {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = new CustomerAddressEntity();

        $this->enderecoServiceMock->expects($this->once())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(true);

        $this->enderecoServiceMock->isImport = true;

        $reflection = new \ReflectionClass($this->insurance);
        $method = $reflection->getMethod('canValidate');

        $result = $method->invoke($this->insurance, $addressEntity, $salesChannelId);

        $this->assertTrue($result);
    }

    /**
     * Tests that canValidate returns false when no validation scenarios apply.
     *
     * Validates the business rules that prevent validation when no
     * applicable validation scenarios are active.
     */
    #[DataProvider('validationScenariosProvider')]
    public function testCanValidateWithVariousScenarios(
        bool $existingEnabled,
        bool $paypalEnabled,
        bool $importEnabled,
        bool $expected
    ): void {
        $salesChannelId = 'test-sales-channel';
        $addressEntity = new CustomerAddressEntity();

        $this->enderecoServiceMock->expects($this->once())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn($existingEnabled);

        if ($existingEnabled) {
            $this->enderecoServiceMock->expects($this->once())
                ->method('isAddressFromRemote')
                ->with($addressEntity)
                ->willReturn(false);

            $this->enderecoServiceMock->expects($this->once())
                ->method('isAddressRecent')
                ->with($addressEntity)
                ->willReturn(false);

            $this->processContextServiceMock->expects($this->once())
                ->method('isStorefront')
                ->willReturn(true);
        }

        $this->enderecoServiceMock->expects($this->once())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn($paypalEnabled);

        if ($paypalEnabled && !$existingEnabled) {
            $this->enderecoServiceMock->expects($this->once())
                ->method('isAddressFromPayPal')
                ->with($addressEntity)
                ->willReturn(true);

            $this->processContextServiceMock->expects($this->once())
                ->method('isStorefront')
                ->willReturn(true);
        }

        $this->enderecoServiceMock->expects($this->once())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn($importEnabled);

        if ($importEnabled) {
            $this->enderecoServiceMock->isImport = true;
        }

        $reflection = new \ReflectionClass($this->insurance);
        $method = $reflection->getMethod('canValidate');

        $result = $method->invoke($this->insurance, $addressEntity, $salesChannelId);

        $this->assertSame($expected, $result);
    }

    /**
     * Provides test data for different AMS status values.
     *
     * @return Generator<string, array{string|null, bool}>
     */
    public static function amsStatusValidationProvider(): Generator
    {
        yield 'empty status' => [null, true];
        yield 'empty string' => ['', true];
        yield 'not checked constant' => [EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED, true];
        yield 'minor correction status' => [EnderecoBaseAddressExtensionEntity::AMS_STATUS_MINOR_CORRECTION, false];
        yield 'automatically selected' => [
            EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_AUTOMATICALLY,
            false
        ];
        yield 'customer selected' => [EnderecoBaseAddressExtensionEntity::AMS_STATUS_SELECTED_BY_CUSTOMER, false];
        yield 'custom status' => ['custom_status', false];
    }

    /**
     * Provides test data for validation scenarios.
     *
     * @return Generator<string, array{bool, bool, bool, bool}>
     */
    public static function validationScenariosProvider(): Generator
    {
        yield 'existing customer valid' => [true, false, false, true];
        yield 'paypal checkout valid' => [false, true, false, true];
        yield 'import process valid' => [false, false, true, true];
        yield 'multiple conditions valid' => [true, true, false, true];
        yield 'no conditions met' => [false, false, false, false];
    }

    /**
     * Provides test data for extension scenarios.
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
        string $amsStatus = EnderecoCustomerAddressExtensionEntity::AMS_STATUS_NOT_CHECKED
    ): CustomerAddressEntity {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId('test-address-id');

        $extension = new EnderecoCustomerAddressExtensionEntity();
        $extension->setAmsStatus($amsStatus);

        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $extension);

        return $addressEntity;
    }

    /**
     * Sets up valid context conditions for address validation.
     */
    private function setupValidContext(string $salesChannelId): void
    {
        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->willReturn($salesChannelId);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isEnderecoPluginActive')
            ->with($salesChannelId)
            ->willReturn(true);
    }

    /**
     * Sets up conditions where validation is allowed.
     */
    private function setupValidationAllowed(string $salesChannelId): void
    {
        $this->enderecoServiceMock->expects($this->atLeastOnce())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->atLeastOnce())
            ->method('isAddressFromRemote')
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->atLeastOnce())
            ->method('isAddressRecent')
            ->willReturn(false);

        $this->processContextServiceMock->expects($this->atLeastOnce())
            ->method('isStorefront')
            ->willReturn(true);

        $this->enderecoServiceMock->expects($this->atLeastOnce())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->atLeastOnce())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);
    }

    /**
     * Sets up conditions where validation is not allowed.
     */
    private function setupValidationNotAllowed(string $salesChannelId): void
    {
        $this->enderecoServiceMock->expects($this->once())
            ->method('isExistingAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isPayPalCheckoutAddressCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);

        $this->enderecoServiceMock->expects($this->once())
            ->method('isImportExportCheckFeatureEnabled')
            ->with($salesChannelId)
            ->willReturn(false);
    }

    /**
     * Creates a successful address check result stub.
     */
    private function createSuccessfulAddressCheckResult(string $sessionId): AddressCheckResult&MockObject
    {
        $result = $this->createMock(AddressCheckResult::class);
        $result->method('getUsedSessionId')->willReturn($sessionId);

        return $result;
    }
}
