<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\DTO\CustomerAddressDTO;
use Endereco\Shopware6Client\DTO\SplitStreetResultDto;
use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Model\CustomerAddressPersistenceStrategy;
use Endereco\Shopware6Client\Service\AddressCheck\AdditionalAddressFieldCheckerInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressCorrection\StreetSplitterInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressPersistenceStrategyProviderInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance;
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

#[CoversClass(StreetIsSplitInsurance::class)]
class StreetIsSplitInsuranceTest extends TestCase
{
    private CountryCodeFetcherInterface&MockObject $countryCodeFetcherMock;
    private StreetSplitterInterface&MockObject $streetSplitterMock;
    private EnderecoService&MockObject $enderecoServiceMock;
    private AddressPersistenceStrategyProviderInterface&MockObject $addressPersistenceStrategyProviderMock;
    private AdditionalAddressFieldCheckerInterface&MockObject $additionalAddressFieldCheckerMock;
    private ProcessContextService&MockObject $processContextServiceMock;
    private StreetIsSplitInsurance $insurance;

    protected function setUp(): void
    {
        $this->countryCodeFetcherMock = $this->createMock(CountryCodeFetcherInterface::class);
        $this->streetSplitterMock = $this->createMock(StreetSplitterInterface::class);
        $this->enderecoServiceMock = $this->createMock(EnderecoService::class);
        $this->addressPersistenceStrategyProviderMock = $this->createMock(
            AddressPersistenceStrategyProviderInterface::class
        );
        $this->additionalAddressFieldCheckerMock = $this->createMock(AdditionalAddressFieldCheckerInterface::class);
        $this->processContextServiceMock = $this->createMock(ProcessContextService::class);

        $this->insurance = new StreetIsSplitInsurance(
            $this->countryCodeFetcherMock,
            $this->streetSplitterMock,
            $this->enderecoServiceMock,
            $this->addressPersistenceStrategyProviderMock,
            $this->additionalAddressFieldCheckerMock,
            $this->processContextServiceMock
        );
    }

    /**
     * Tests that getPriority returns expected high priority value.
     *
     * Validates the priority system used for ordering street splitting
     * before other address integrity services that depend on split data.
     */
    public function testGetPriorityReturnsHighPriorityValue(): void
    {
        $priority = StreetIsSplitInsurance::getPriority();

        $this->assertSame(-10, $priority);
    }

    /**
     * Tests that ensure skips processing when not in storefront context.
     *
     * Validates that street splitting only occurs in storefront contexts
     * where address validation is required for customer-facing operations.
     */
    public function testEnsureSkipsProcessingWhenNotStorefront(): void
    {
        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(false);

        $this->countryCodeFetcherMock->expects($this->never())->method('fetchCountryCodeByCountryIdAndContext');
        $this->streetSplitterMock->expects($this->never())->method('splitStreet');

        $addressEntity = new CustomerAddressEntity();
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure throws exception when address extension is missing.
     *
     * Validates the critical validation that ensures address extensions
     * exist before attempting street splitting operations.
     */
    public function testEnsureThrowsExceptionWhenAddressExtensionMissing(): void
    {
        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);

        $addressEntity = new CustomerAddressEntity();
        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure throws exception when address extension has wrong type.
     *
     * Validates type safety for address extensions to prevent
     * processing errors with incompatible extension types.
     */
    public function testEnsureThrowsExceptionWhenAddressExtensionWrongType(): void
    {
        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);

        $addressEntity = new CustomerAddressEntity();
        $wrongExtension = new class extends Struct {
        };
        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $wrongExtension);
        $context = Context::createCliContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The address extension should be set at this point');

        $this->insurance->ensure($addressEntity, $context);
    }

    // Tests for empty street handling

    /**
     * Tests that ensure skips processing when street is empty.
     *
     * Validates that the insurance gracefully handles addresses
     * without street information rather than attempting invalid splits.
     */
    #[DataProvider('emptyStreetScenariosProvider')]
    public function testEnsureSkipsProcessingWhenStreetEmpty(string $street): void
    {
        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);

        $this->countryCodeFetcherMock->expects($this->once())
            ->method('fetchCountryCodeByCountryIdAndContext')
            ->willReturn('DE');

        $this->additionalAddressFieldCheckerMock->expects($this->once())
            ->method('hasAdditionalAddressField')
            ->willReturn(false);

        $this->streetSplitterMock->expects($this->never())->method('splitStreet');

        $addressEntity = $this->createCustomerAddressEntity($street);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that ensure processes whitespace-only street normally.
     *
     * Validates that whitespace-only streets are not treated as empty
     * since empty() in PHP returns false for whitespace-only strings.
     */
    public function testEnsureProcessesWhitespaceOnlyStreet(): void
    {
        $whitespaceStreet = '   ';
        $countryId = 'country-id-123';
        $salesChannelId = 'channel-id-456';

        $this->setupStorefrontContext();
        $this->setupCountryCodeFetching($countryId, 'DE');
        $this->setupAdditionalFieldsDisabled();
        $this->setupEnderecoService($salesChannelId);

        $splitResult = new SplitStreetResultDto(
            $whitespaceStreet,
            '',
            '',
            null
        );

        $this->setupStreetSplitter($whitespaceStreet, null, 'DE', $salesChannelId, $splitResult);
        $this->setupPersistenceStrategy($splitResult);

        $addressEntity = $this->createCustomerAddressEntity($whitespaceStreet, $countryId);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests successful street splitting with various address formats.
     *
     * Validates the core business logic that processes street addresses
     * and delegates to persistence strategies for different country formats.
     */
    #[DataProvider('streetSplittingScenariosProvider')]
    public function testEnsureSuccessfullyProcessesStreetSplitting(
        string $fullStreet,
        string $countryCode,
        string $expectedStreetName,
        string $expectedBuildingNumber
    ): void {
        $countryId = 'country-id-123';
        $salesChannelId = 'channel-id-456';

        $this->setupStorefrontContext();
        $this->setupCountryCodeFetching($countryId, $countryCode);
        $this->setupAdditionalFieldsDisabled();
        $this->setupEnderecoService($salesChannelId);

        $splitResult = new SplitStreetResultDto(
            $fullStreet,
            $expectedStreetName,
            $expectedBuildingNumber,
            null
        );

        $this->setupStreetSplitter($fullStreet, null, $countryCode, $salesChannelId, $splitResult);
        $this->setupPersistenceStrategy($splitResult);

        $addressEntity = $this->createCustomerAddressEntity($fullStreet, $countryId);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests street splitting with additional address fields enabled.
     *
     * Validates the conditional logic that extracts additional address
     * information when the system is configured to use extended fields.
     */
    #[DataProvider('additionalFieldsScenariosProvider')]
    public function testEnsureHandlesAdditionalAddressFields(
        bool $hasAdditionalField,
        ?string $fieldName,
        ?string $additionalLine1,
        ?string $additionalLine2,
        ?string $expectedAdditionalInfo
    ): void {
        $fullStreet = 'Test Street 123';
        $countryCode = 'DE';
        $countryId = 'country-id';
        $salesChannelId = 'channel-id';

        $this->setupStorefrontContext();
        $this->setupCountryCodeFetching($countryId, $countryCode);
        $this->setupEnderecoService($salesChannelId);

        $this->additionalAddressFieldCheckerMock->expects($this->once())
            ->method('hasAdditionalAddressField')
            ->willReturn($hasAdditionalField);

        if ($hasAdditionalField) {
            $this->additionalAddressFieldCheckerMock->expects($this->once())
                ->method('getAvailableAdditionalAddressFieldName')
                ->willReturn($fieldName);
        }

        $splitResult = new SplitStreetResultDto(
            $fullStreet,
            'Test Street',
            '123',
            $expectedAdditionalInfo
        );

        $this->streetSplitterMock->expects($this->once())
            ->method('splitStreet')
            ->with(
                $this->identicalTo($fullStreet),
                $this->identicalTo($expectedAdditionalInfo),
                $this->identicalTo($countryCode),
                $this->anything(),
                $this->identicalTo($salesChannelId)
            )
            ->willReturn($splitResult);

        $this->setupPersistenceStrategy($splitResult);

        $addressEntity = $this->createCustomerAddressEntityWithAdditionalFields(
            $fullStreet,
            $countryId,
            $additionalLine1,
            $additionalLine2
        );
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that country code defaults to Germany when unknown.
     *
     * Validates the fallback behavior that ensures processing continues
     * with a sensible default when country information is unavailable.
     */
    public function testEnsureUsesDefaultCountryCodeWhenUnknown(): void
    {
        $countryId = 'unknown-country-id';
        $defaultCountryCode = 'DE';
        $fullStreet = 'Unknown Street 456';

        $this->setupStorefrontContext();
        $this->setupAdditionalFieldsDisabled();
        $this->setupEnderecoService('channel-id');

        $this->countryCodeFetcherMock->expects($this->once())
            ->method('fetchCountryCodeByCountryIdAndContext')
            ->with(
                $this->identicalTo($countryId),
                $this->anything(),
                $this->identicalTo($defaultCountryCode)
            )
            ->willReturn($defaultCountryCode);

        $splitResult = new SplitStreetResultDto(
            $fullStreet,
            'Unknown Street',
            '456',
            null
        );

        $this->streetSplitterMock->expects($this->once())
            ->method('splitStreet')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->identicalTo($defaultCountryCode),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($splitResult);

        $this->setupPersistenceStrategy($splitResult);

        $addressEntity = $this->createCustomerAddressEntity($fullStreet, $countryId);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that persistence strategy is properly invoked with split results.
     *
     * Validates the integration with address persistence strategies
     * that handle saving the split street components based on configuration.
     */
    public function testEnsureInvokesPersistenceStrategyWithSplitResults(): void
    {
        $fullStreet = 'Strategy Test Street 789';
        $streetName = 'Strategy Test Street';
        $buildingNumber = '789';
        $additionalInfo = 'Test Info';

        $this->setupStorefrontContext();
        $this->setupCountryCodeFetching('country-id-123', 'DE');
        $this->setupAdditionalFieldsDisabled();
        $this->setupEnderecoService('channel-id');

        $splitResult = new SplitStreetResultDto(
            $fullStreet,
            $streetName,
            $buildingNumber,
            $additionalInfo
        );

        $this->streetSplitterMock->expects($this->once())
            ->method('splitStreet')
            ->willReturn($splitResult);

        $persistenceStrategyMock = $this->createMock(CustomerAddressPersistenceStrategy::class);

        $this->addressPersistenceStrategyProviderMock->expects($this->once())
            ->method('getStrategy')
            ->with(
                $this->callback(function ($dto) {
                    $this->assertInstanceOf(CustomerAddressDTO::class, $dto);
                    return true;
                }),
                $this->anything()
            )
            ->willReturn($persistenceStrategyMock);

        $persistenceStrategyMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->identicalTo($fullStreet),
                $this->identicalTo($additionalInfo),
                $this->identicalTo($streetName),
                $this->identicalTo($buildingNumber),
                $this->callback(function ($dto) {
                    $this->assertInstanceOf(CustomerAddressDTO::class, $dto);
                    return true;
                })
            );

        $addressEntity = $this->createCustomerAddressEntity($fullStreet);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Tests that proper CustomerAddressDTO is created for persistence.
     *
     * Validates the data transfer object construction that provides
     * all necessary information for persistence strategy execution.
     */
    public function testEnsureCreatesProperCustomerAddressDTO(): void
    {
        $fullStreet = 'DTO Test Street 321';

        $this->setupStorefrontContext();
        $this->setupCountryCodeFetching('country-id-123', 'DE');
        $this->setupAdditionalFieldsDisabled();
        $this->setupEnderecoService('channel-id');

        $splitResult = new SplitStreetResultDto($fullStreet, 'DTO Test Street', '321', null);
        $this->streetSplitterMock->expects($this->once())->method('splitStreet')->willReturn($splitResult);

        $persistenceStrategyMock = $this->createMock(CustomerAddressPersistenceStrategy::class);

        $this->addressPersistenceStrategyProviderMock->expects($this->once())
            ->method('getStrategy')
            ->with(
                $this->callback(function ($dto) use ($fullStreet) {
                    $this->assertInstanceOf(CustomerAddressDTO::class, $dto);
                    $this->assertInstanceOf(CustomerAddressEntity::class, $dto->getCustomerAddress());
                    $this->assertInstanceOf(
                        EnderecoCustomerAddressExtensionEntity::class,
                        $dto->getEnderecoCustomerAddressExtension()
                    );
                    $this->assertSame($fullStreet, $dto->getCustomerAddress()->getStreet());
                    $this->assertEmpty($dto->getPostData());
                    return true;
                }),
                $this->anything()
            )
            ->willReturn($persistenceStrategyMock);

        $persistenceStrategyMock->expects($this->once())->method('execute');

        $addressEntity = $this->createCustomerAddressEntity($fullStreet);
        $context = Context::createCliContext();

        $this->insurance->ensure($addressEntity, $context);
    }

    /**
     * Provides test data for street splitting scenarios.
     *
     * @return Generator<string, array{string, string, string, string}>
     */
    public static function streetSplittingScenariosProvider(): Generator
    {
        yield 'german address' => [
            'Musterstraße 123',
            'DE',
            'Musterstraße',
            '123'
        ];
        yield 'us address' => [
            '123 Main Street',
            'US',
            'Main Street',
            '123'
        ];
        yield 'complex german address' => [
            'Am Stadtpark 12a',
            'DE',
            'Am Stadtpark',
            '12a'
        ];
    }

    /**
     * Provides test data for empty or invalid street scenarios.
     *
     * @return Generator<string, array{string}>
     */
    public static function emptyStreetScenariosProvider(): Generator
    {
        yield 'empty string' => [''];
    }

    /**
     * Provides test data for additional address field scenarios.
     *
     * @return Generator<string, array{bool, string|null, string|null, string|null, string|null}>
     */
    public static function additionalFieldsScenariosProvider(): Generator
    {
        yield 'no additional fields' => [false, null, null, null, null];
        yield 'additional line 1 field' => [true, 'additionalAddressLine1', 'Suite 100', null, 'Suite 100'];
        yield 'additional line 2 field' => [true, 'additionalAddressLine2', null, 'Building B', 'Building B'];
        yield 'both fields with line 1 selected' => [true, 'additionalAddressLine1', 'Floor 3', 'Wing A', 'Floor 3'];
        yield 'invalid field name' => [true, 'invalidField', 'Test', 'Test2', ''];
    }

    private function createCustomerAddressEntity(
        string $street = 'Test Street 123',
        string $countryId = 'country-id-123'
    ): CustomerAddressEntity {
        $addressEntity = new CustomerAddressEntity();
        $addressEntity->setId('address-id-123');
        $addressEntity->setStreet($street);
        $addressEntity->setCountryId($countryId);

        $extension = new EnderecoCustomerAddressExtensionEntity();
        $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $extension);

        return $addressEntity;
    }

    private function createCustomerAddressEntityWithAdditionalFields(
        string $street,
        string $countryId,
        ?string $additionalLine1 = null,
        ?string $additionalLine2 = null
    ): CustomerAddressEntity {
        $addressEntity = $this->createCustomerAddressEntity($street, $countryId);
        $addressEntity->setAdditionalAddressLine1($additionalLine1 ?? '');
        $addressEntity->setAdditionalAddressLine2($additionalLine2 ?? '');

        return $addressEntity;
    }

    private function setupStorefrontContext(): void
    {
        $this->processContextServiceMock->expects($this->once())
            ->method('isStorefront')
            ->willReturn(true);
    }

    private function setupCountryCodeFetching(string $countryId, string $countryCode): void
    {
        $this->countryCodeFetcherMock->expects($this->once())
            ->method('fetchCountryCodeByCountryIdAndContext')
            ->with(
                $this->identicalTo($countryId),
                $this->anything(),
                $this->identicalTo('DE')
            )
            ->willReturn($countryCode);
    }

    private function setupAdditionalFieldsDisabled(): void
    {
        $this->additionalAddressFieldCheckerMock->expects($this->once())
            ->method('hasAdditionalAddressField')
            ->willReturn(false);
    }

    private function setupEnderecoService(string $salesChannelId): void
    {
        $this->enderecoServiceMock->expects($this->once())
            ->method('fetchSalesChannelId')
            ->willReturn($salesChannelId);
    }

    private function setupStreetSplitter(
        string $fullStreet,
        ?string $additionalInfo,
        string $countryCode,
        string $salesChannelId,
        SplitStreetResultDto $splitResult
    ): void {
        $this->streetSplitterMock->expects($this->once())
            ->method('splitStreet')
            ->with(
                $this->identicalTo($fullStreet),
                $this->identicalTo($additionalInfo),
                $this->identicalTo($countryCode),
                $this->anything(),
                $this->identicalTo($salesChannelId)
            )
            ->willReturn($splitResult);
    }

    private function setupPersistenceStrategy(SplitStreetResultDto $splitResult): void
    {
        $persistenceStrategyMock = $this->createMock(CustomerAddressPersistenceStrategy::class);

        $this->addressPersistenceStrategyProviderMock->expects($this->once())
            ->method('getStrategy')
            ->willReturn($persistenceStrategyMock);

        $persistenceStrategyMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->identicalTo($splitResult->getFullStreet()),
                $this->identicalTo($splitResult->getAdditionalInfo()),
                $this->identicalTo($splitResult->getStreetName()),
                $this->identicalTo($splitResult->getBuildingNumber()),
                $this->anything()
            );
    }
}
