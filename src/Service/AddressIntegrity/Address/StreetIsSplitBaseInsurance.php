<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Address;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

abstract class StreetIsSplitBaseInsurance
{
    private IsStreetSplitRequiredCheckerInterface $isStreetSplitRequiredChecker;
    private CountryCodeFetcherInterface $countryCodeFetcher;
    private EnderecoService $enderecoService;

    protected function __construct(
        IsStreetSplitRequiredCheckerInterface $isStreetSplitRequiredChecker,
        CountryCodeFetcherInterface $countryCodeFetcher,
        EnderecoService $enderecoService
    ) {
        $this->isStreetSplitRequiredChecker = $isStreetSplitRequiredChecker;
        $this->countryCodeFetcher = $countryCodeFetcher;
        $this->enderecoService = $enderecoService;
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @param EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension
     * @param string $salesChannelId
     * @param Context $context
     */
    protected function doEnsure(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        string $salesChannelId,
        Context $context
    ): void {
        $fullStreet = $addressEntity->getStreet();
        if (empty($fullStreet)) {
            return;
        }

        $isStreetSplitRequired = $this->isStreetSplitRequiredChecker->checkIfStreetSplitIsRequired(
            $addressEntity,
            $addressExtension,
            $context
        );
        if (!$isStreetSplitRequired) {
            return;
        }

        // If country is unknown, use Germany as default
        $countryCode = $this->countryCodeFetcher->fetchCountryCodeByCountryIdAndContext(
            $addressEntity->getCountryId(),
            $context,
            'DE'
        );

        list($streetName, $buildingNumber) = $this->enderecoService->splitStreet(
            $fullStreet,
            $countryCode,
            $context,
            $salesChannelId
        );

        $this->getAddressExtensionRepository()->upsert(
            [
                [
                    'addressId' => $addressEntity->getId(),
                    'street' => $streetName,
                    'houseNumber' => $buildingNumber
                ]
            ],
            $context
        );

        $addressExtension->setStreet($streetName);
        $addressExtension->setHouseNumber($buildingNumber);
    }

    abstract protected function getAddressExtensionRepository(): EntityRepository;
}
