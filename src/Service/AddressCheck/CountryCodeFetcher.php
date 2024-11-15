<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;

final class CountryCodeFetcher implements CountryCodeFetcherInterface
{
    private EntityRepository $countryRepository;

    public function __construct(
        EntityRepository $countryRepository
    ) {
        $this->countryRepository = $countryRepository;
    }

    public function fetchCountryCodeByCountryIdAndContext(
        string $countryId,
        Context $context,
        string $defaultCountryCode = 'DE'
    ): string {
        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search(new Criteria([$countryId]), $context)->first();

        // Check if the country was found
        if ($country !== null) {
            // If country is found, get the ISO code
            $countryCode = $country->getIso() ?? $defaultCountryCode;
        } else {
            // If no country is found, default to the provided default country code
            $countryCode = $defaultCountryCode;
        }

        return $countryCode;
    }
}
