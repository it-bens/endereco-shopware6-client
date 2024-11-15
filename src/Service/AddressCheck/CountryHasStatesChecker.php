<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;

final class CountryHasStatesChecker implements CountryHasStatesCheckerInterface
{
    private EntityRepository $countryRepository;

    public function __construct(
        EntityRepository $countryRepository
    ) {
        $this->countryRepository = $countryRepository;
    }

    public function hasCountryStates(string $countryId, Context $context): bool
    {
        $criteria = new Criteria([$countryId]);
        $criteria->addAssociation('states');

        /** @var CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        // Check if the country was found and if it has more than one state
        // If so, return true, indicating that the country has subdivisions
        if (!is_null($country->getStates()) && $country->getStates()->count() > 1) {
            return true;
        }

        // If the country is not found or does not have more than one state, return false
        return false;
    }
}
