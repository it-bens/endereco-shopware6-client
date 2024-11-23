<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Check;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

final class IsStreetSplitRequiredChecker implements IsStreetSplitRequiredCheckerInterface
{
    private EnderecoService $enderecoService;
    private CountryCodeFetcherInterface $countryCodeFetcher;

    public function __construct(
        EnderecoService $enderecoService,
        CountryCodeFetcherInterface $countryCodeFetcher
    ) {
        $this->enderecoService = $enderecoService;
        $this->countryCodeFetcher = $countryCodeFetcher;
    }

    public function checkIfStreetSplitIsRequired(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        Context $context
    ): bool {
        // Construct the expected full street string
        $expectedFullStreet = $this->enderecoService->buildFullStreet(
            $addressExtension->getStreet(),
            $addressExtension->getHouseNumber(),
            $this->countryCodeFetcher->fetchCountryCodeByCountryIdAndContext(
                $addressEntity->getCountryId(),
                $context,
                'DE'
            )
        );

        // Fetch the current full street string from the address entity
        $currentFullStreet = $addressEntity->getStreet();

        // Compare the expected and current full street strings and return the result
        return $expectedFullStreet !== $currentFullStreet;
    }
}
