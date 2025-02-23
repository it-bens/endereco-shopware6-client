<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressCorrection\StreetSplitterInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance\AddressPersistenceStrategyProviderInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Ensures street addresses are properly split into street name and building number
 */
final class StreetIsSplitInsurance implements IntegrityInsurance
{
    private CountryCodeFetcherInterface $countryCodeFetcher;
    private StreetSplitterInterface $streetSplitter;
    private EnderecoService $enderecoService;
    private EntityRepository $addressExtensionRepository;
    private AddressPersistenceStrategyProviderInterface $addressPersistenceStrategyProvider;

    public function __construct(
        CountryCodeFetcherInterface $countryCodeFetcher,
        StreetSplitterInterface $streetSplitter,
        EnderecoService $enderecoService,
        EntityRepository $addressExtensionRepository,
        AddressPersistenceStrategyProviderInterface $addressPersistenceStrategyProvider
    ) {
        $this->countryCodeFetcher = $countryCodeFetcher;
        $this->streetSplitter = $streetSplitter;
        $this->enderecoService = $enderecoService;
        $this->addressExtensionRepository = $addressExtensionRepository;
        $this->addressPersistenceStrategyProvider = $addressPersistenceStrategyProvider;
    }

    public static function getPriority(): int
    {
        return -10;
    }

    /**
     * Ensures that the full street address of a given address entity is properly split into street name and building
     * number.
     *
     * This method accepts an AddressEntity. It retrieves the corresponding EnderecoAddressExtension for the address
     * and the full street address stored in the AddressEntity. The street split is always executed.
     *
     * If the street address is not empty, the method splits the full street address into street name,
     * building number and maybe more data using the 'splitStreet' method of the Endereco service. The country code for
     * splitting the street is retrieved using the 'getCountryCodeById' method (defaulting to 'DE' if unknown). The
     * split street name and building number are then saved back into the EnderecoAddressExtension for the
     * address.
     *
     * Which data of the result should persisted and to which field is decided by a address persistence strategy.
     * This strategies are provided based on the system configuration and calculate the actions
     * considering the address extension data.
     */
    public function ensure(CustomerAddressEntity $addressEntity, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $fullStreet = $addressEntity->getStreet();
        if (empty($fullStreet)) {
            return;
        }

        // If country is unknown, use Germany as default
        $countryCode = $this->countryCodeFetcher->fetchCountryCodeByCountryIdAndContext(
            $addressEntity->getCountryId(),
            $context,
            'DE'
        );

        $addressPersistenceStrategy = $this->addressPersistenceStrategyProvider->getStrategy(
            $addressExtension->isPayPalAddress(),
            $addressExtension->isAmazonPayAddress(),
            $context
        );

        $additionalInfo = $addressPersistenceStrategy->getAdditionalInfoForStreetSplit($addressEntity);
        list($normalizedFullStreet, $streetName, $buildingNumber, $normalizedAdditionalInfo) = $this->streetSplitter->splitStreet(
            $fullStreet,
            $additionalInfo,
            $countryCode,
            $context,
            $this->enderecoService->fetchSalesChannelId($context)
        );

        // We persist the result of the street split to the address and its extension to the database
        // and sync the data to the objects. The data selection and mapping is done by the address persistence strategy
        // based on the system configuration and the address extension data. streetSplit sometimes normalizes the data
        // therefore, we need to overwrite the original input or the split will be triggered endlessly in some cases.
        $isPersistenceRequired = $addressPersistenceStrategy->isAddressExtensionWithAddressPersistenceRequired(
            $normalizedFullStreet,
            $streetName,
            $buildingNumber,
            $normalizedAdditionalInfo,
            $addressEntity,
            $addressExtension
        );
        if ($isPersistenceRequired === false) {
            return;
        }

        $payload = $addressPersistenceStrategy->buildAddressExtensionWithAddressUpsertPayload(
            $addressEntity->getId(),
            $normalizedFullStreet,
            $streetName,
            $buildingNumber,
            $normalizedAdditionalInfo
        );
        $this->addressExtensionRepository->upsert([$payload], $context);

        $addressPersistenceStrategy->updateAddress(
            $normalizedFullStreet,
            $streetName,
            $buildingNumber,
            $normalizedAdditionalInfo,
            $addressEntity,
            $addressExtension
        );
    }
}
