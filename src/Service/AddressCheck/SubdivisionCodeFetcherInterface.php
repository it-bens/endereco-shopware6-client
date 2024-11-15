<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;

interface SubdivisionCodeFetcherInterface
{
    /**
     * Fetches the ISO code of the subdivision (state) associated with a given subdivision ID.
     *
     * This method performs a search in the country state repository for a subdivision matching
     * the provided ID. If a subdivision is found, its ISO code is retrieved, converted to uppercase,
     * and returned. If no subdivision is found, null is returned.
     *
     * @param string $countryStateId The ID of the subdivision (country state) whose ISO code is to be fetched.
     * @param Context $context The context which includes details of the event triggering this method.
     *
     * @return string|null The ISO code of the subdivision if found, or a null if not.
     */
    public function fetchSubdivisionCodeByCountryStateId(string $countryStateId, Context $context): ?string;
}
