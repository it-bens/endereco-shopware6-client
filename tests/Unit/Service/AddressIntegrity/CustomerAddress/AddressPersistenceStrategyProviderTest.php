<?php

declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 */

namespace Endereco\Shopware6Client\Tests\Unit\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\DTO\CustomerAddressDTO;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\DoNothing;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\OverwriteNativeAndExtensionPostData;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\PersistNativeAndExtensionFields;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\PersistOnlyExtensionFields;
use Endereco\Shopware6Client\Model\CustomerAddressCorrectionScope;
use Endereco\Shopware6Client\Service\AddressCheck\AdditionalAddressFieldCheckerInterface;
use Endereco\Shopware6Client\Service\AddressCorrection\AddressCorrectionScopeBuilderInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressPersistenceStrategyProvider;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

#[CoversClass(AddressPersistenceStrategyProvider::class)]
class AddressPersistenceStrategyProviderTest extends TestCase
{
    private AddressCorrectionScopeBuilderInterface&MockObject $addressCorrectionScopeBuilderMock;
    private AdditionalAddressFieldCheckerInterface&MockObject $additionalAddressFieldCheckerMock;
    /** @var EntityRepository<CustomerAddressCollection>&MockObject */
    private EntityRepository&MockObject $customerAddressRepositoryMock;
    /** @var EntityRepository<EnderecoCustomerAddressExtensionCollection>&MockObject */
    private EntityRepository&MockObject $customerAddressExtensionRepositoryMock;
    private Context&MockObject $contextMock;
    private AddressPersistenceStrategyProvider $provider;

    protected function setUp(): void
    {
        $this->addressCorrectionScopeBuilderMock = $this->createMock(AddressCorrectionScopeBuilderInterface::class);
        $this->additionalAddressFieldCheckerMock = $this->createMock(AdditionalAddressFieldCheckerInterface::class);
        $this->customerAddressRepositoryMock = $this->createMock(EntityRepository::class);
        $this->customerAddressExtensionRepositoryMock = $this->createMock(EntityRepository::class);
        $this->contextMock = $this->createMock(Context::class);

        $this->provider = new AddressPersistenceStrategyProvider(
            $this->addressCorrectionScopeBuilderMock,
            $this->additionalAddressFieldCheckerMock,
            $this->customerAddressRepositoryMock,
            $this->customerAddressExtensionRepositoryMock
        );
    }

    /**
     * Tests that OverwriteNativeAndExtensionPostData strategy is returned when post data is present.
     * This strategy handles cases where the address data comes from form submission.
     */
    public function testGetStrategyReturnsOverwriteNativeAndExtensionPostDataWhenPostDataExists(): void
    {
        $postData = ['street' => 'Test Street'];
        $customerAddressDTO = new CustomerAddressDTO(null, null, $postData);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(OverwriteNativeAndExtensionPostData::class, $strategy);
    }

    /**
     * Tests that OverwriteNativeAndExtensionPostData strategy is returned
     * even when extension exists but post data is present.
     * Post data takes precedence over extension-based strategies.
     */
    public function testGetStrategyReturnsOverwriteNativeAndExtensionPostDataWhenPostDataExistsEvenWithExtension(): void
    {
        $postData = ['street' => 'Test Street'];
        $extensionEntityStub = $this->createStub(EnderecoCustomerAddressExtensionEntity::class);
        $customerAddressDTO = new CustomerAddressDTO(null, $extensionEntityStub, $postData);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(OverwriteNativeAndExtensionPostData::class, $strategy);
    }

    /**
     * Tests that OverwriteNativeAndExtensionPostData strategy is consistently returned for various post data formats.
     * Verifies that the strategy selection logic correctly prioritizes post data regardless of its content structure.
     *
     * @param array<string, mixed> $postData
     */
    #[DataProvider('postDataScenariosProvider')]
    public function testGetStrategyConsistentlyReturnsOverwriteStrategyForVariousPostDataFormats(array $postData): void
    {
        $customerAddressDTO = new CustomerAddressDTO(null, null, $postData);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(OverwriteNativeAndExtensionPostData::class, $strategy);
    }

    /**
     * Tests that the strategy provider correctly handles empty post data array.
     * Empty array should be treated as "no post data" and follow extension-based logic.
     */
    public function testGetStrategyTreatsEmptyPostDataArrayAsNoPostData(): void
    {
        $postData = [];
        $customerAddressDTO = new CustomerAddressDTO(null, null, $postData);

        $this->addressCorrectionScopeBuilderMock
            ->expects($this->never())
            ->method('buildCustomerAddressCorrectionScope');

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(DoNothing::class, $strategy);
    }

    /**
     * Tests that PersistNativeAndExtensionFields strategy is returned when both native
     * and extension fields can be written.
     * This happens when the extension exists and the correction scope allows both types of writes.
     */
    public function testGetStrategyReturnsPersistNativeAndExtensionFieldsWhenBothFieldsCanBeWritten(): void
    {
        $postData = [];
        $extensionEntityStub = $this->createStub(EnderecoCustomerAddressExtensionEntity::class);
        $customerAddressDTO = new CustomerAddressDTO(null, $extensionEntityStub, $postData);

        $correctionScopeStub = $this->createStub(CustomerAddressCorrectionScope::class);
        $correctionScopeStub->method('canWriteNativeFields')->willReturn(true);
        $correctionScopeStub->method('canWriteExtensionFields')->willReturn(true);

        $this->addressCorrectionScopeBuilderMock
            ->expects($this->once())
            ->method('buildCustomerAddressCorrectionScope')
            ->with($extensionEntityStub, $this->contextMock)
            ->willReturn($correctionScopeStub);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(PersistNativeAndExtensionFields::class, $strategy);
    }

    /**
     * Tests that PersistOnlyExtensionFields strategy is returned when only extension fields can be written.
     * This happens when native field writing is restricted but extension field writing is allowed.
     */
    public function testGetStrategyReturnsPersistOnlyExtensionFieldsWhenOnlyExtensionFieldsCanBeWritten(): void
    {
        $postData = [];
        $extensionEntityStub = $this->createStub(EnderecoCustomerAddressExtensionEntity::class);
        $customerAddressDTO = new CustomerAddressDTO(null, $extensionEntityStub, $postData);

        $correctionScopeStub = $this->createStub(CustomerAddressCorrectionScope::class);
        $correctionScopeStub->method('canWriteNativeFields')->willReturn(false);
        $correctionScopeStub->method('canWriteExtensionFields')->willReturn(true);

        $this->addressCorrectionScopeBuilderMock
            ->expects($this->once())
            ->method('buildCustomerAddressCorrectionScope')
            ->with($extensionEntityStub, $this->contextMock)
            ->willReturn($correctionScopeStub);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(PersistOnlyExtensionFields::class, $strategy);
    }

    /**
     * Tests that DoNothing strategy is returned when extension exists but no fields can be written.
     * This preserves data integrity when permissions or business rules prevent modifications.
     */
    #[DataProvider('doNothingRulesProvider')]
    public function testGetStrategyReturnsDoNothingWhenNoFieldsCanBeWritten(
        bool $canWriteNative,
        bool $canWriteExtension,
        string $scenario
    ): void {
        $postData = [];
        $extensionEntityStub = $this->createStub(EnderecoCustomerAddressExtensionEntity::class);
        $customerAddressDTO = new CustomerAddressDTO(null, $extensionEntityStub, $postData);

        $correctionScopeStub = $this->createStub(CustomerAddressCorrectionScope::class);
        $correctionScopeStub->method('canWriteNativeFields')->willReturn($canWriteNative);
        $correctionScopeStub->method('canWriteExtensionFields')->willReturn($canWriteExtension);

        $this->addressCorrectionScopeBuilderMock
            ->expects($this->once())
            ->method('buildCustomerAddressCorrectionScope')
            ->with($extensionEntityStub, $this->contextMock)
            ->willReturn($correctionScopeStub);

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(DoNothing::class, $strategy, $scenario);
    }

    /**
     * Tests that DoNothing strategy is returned when no extension entity exists and no post data is available.
     * This represents the fallback case when there's nothing to process.
     */
    public function testGetStrategyReturnsDoNothingWhenNoExtensionAndNoPostData(): void
    {
        $postData = [];
        $customerAddressDTO = new CustomerAddressDTO(null, null, $postData);

        $this->addressCorrectionScopeBuilderMock
            ->expects($this->never())
            ->method('buildCustomerAddressCorrectionScope');

        $strategy = $this->provider->getStrategy($customerAddressDTO, $this->contextMock);

        $this->assertInstanceOf(DoNothing::class, $strategy);
    }

    /**
     * Provides test data for different post data scenarios.
     *
     * @return Generator<string, array{array<string, mixed>}>
     */
    public static function postDataScenariosProvider(): Generator
    {
        yield 'single field' => [['street' => 'Main Street']];
        yield 'multiple fields' => [['street' => 'Main Street', 'city' => 'Berlin', 'zipcode' => '12345']];
        yield 'nested data' => [['address' => ['street' => 'Main Street', 'additional' => ['info' => 'Apartment 3A']]]];
        yield 'empty string values' => [['street' => '', 'city' => '']];
        yield 'null values' => [['street' => null, 'city' => 'Berlin']];
    }

    /**
     * Provides test data for scenarios that should return DoNothing strategy.
     *
     * @return Generator<string, array{bool, bool, string}>
     */
    public static function doNothingRulesProvider(): Generator
    {
        yield 'neither native nor extension fields can be written' => [
            false,
            false,
            'No permissions to write any fields'
        ];
        yield 'only native fields can be written but extension cannot' => [
            true,
            false,
            'Extension field write permission denied'
        ];
    }
}
