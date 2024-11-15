<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;

final class SubdivisionCodeFetcher implements SubdivisionCodeFetcherInterface
{
    private EntityRepository $countryStateRepository;

    public function __construct(
        EntityRepository $countryStateRepository
    ) {
        $this->countryStateRepository = $countryStateRepository;
    }

    /**
     * @param string $countryStateId
     * @param Context $context
     *
     * @return string|null
     */
    public function fetchSubdivisionCodeByCountryStateId(string $countryStateId, Context $context): ?string
    {
        /** @var CountryStateEntity|null $state */
        $state = $this->countryStateRepository->search(new Criteria([$countryStateId]), $context)->first();

        if ($state === null) {
            return null;
        }

        // If a subdivision is found, get its ISO code and convert it to uppercase
        return strtoupper($state->getShortCode());
    }
}
