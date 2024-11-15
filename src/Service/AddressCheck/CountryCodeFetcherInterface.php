<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;

interface CountryCodeFetcherInterface
{
    /**
     * Retrieves the ISO code of a country by its ID. If the country is not found or
     * the country ID is missing, a default country code is returned.
     *
     * @param string $countryId The ID of the country.
     * @param Context $context The current context.
     * @param string $defaultCountryCode The default country code to use if the country is not found.
     *
     * @return string Returns the country's ISO code, or the default country code if the country is not found.
     */
    public function fetchCountryCodeByCountryIdAndContext(
        string $countryId,
        Context $context,
        string $defaultCountryCode = 'DE'
    ): string;
}
