<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Endereco\Shopware6Client\Model\AddressCheckPayload;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

final class AddressCheckPayloadBuilder implements AddressCheckPayloadBuilderInterface
{
    private LocaleFetcherInterface $localeFetcher;

    private CountryCodeFetcherInterface $countryCodeFetcher;

    private SubdivisionCodeFetcherInterface $subdivisionCodeFetcher;

    private CountryHasStatesCheckerInterface $countryHasStatesChecker;

    public function __construct(
        LocaleFetcherInterface $localeFetcher,
        CountryCodeFetcherInterface $countryCodeFetcher,
        SubdivisionCodeFetcherInterface $subdivisionCodeFetcher,
        CountryHasStatesCheckerInterface $countryHasStatesChecker
    ) {
        $this->localeFetcher = $localeFetcher;
        $this->countryCodeFetcher = $countryCodeFetcher;
        $this->subdivisionCodeFetcher = $subdivisionCodeFetcher;
        $this->countryHasStatesChecker = $countryHasStatesChecker;
    }

    /**
     * @param string $salesChannelId
     * @param CustomerAddressEntity $addressEntity
     * @param Context $context
     *
     * @return AddressCheckPayload
     */
    public function buildAddressCheckPayload(
        string $salesChannelId,
        CustomerAddressEntity $addressEntity,
        Context $context
    ): AddressCheckPayload {
        try {
            $lang = $this->localeFetcher->fetchLocaleBySalesChannelId($salesChannelId, $context);
        } catch (\Exception $e) {
            $lang = 'de'; // set "de" by default.
        }

        $countryId = $addressEntity->getCountryId();
        $countryCode = $this->countryCodeFetcher->fetchCountryCodeByCountryIdAndContext($countryId, $context);
        $postCode = empty($addressEntity->getZipcode()) ? '' : $addressEntity->getZipcode();
        $cityName = $addressEntity->getCity();
        $streetFull = $addressEntity->getStreet();


        $subdivisionCode = null;
        if ($addressEntity->getCountryStateId() !== null) {
            $_subdivisionCode = $this->subdivisionCodeFetcher->fetchSubdivisionCodeByCountryStateId(
                $addressEntity->getCountryStateId(),
                $context
            );

            if ($_subdivisionCode !== null) {
                $subdivisionCode = $_subdivisionCode;
            }
        }
        if ($subdivisionCode === null && $this->countryHasStatesChecker->hasCountryStates($countryId, $context)) {
            // If a state was not assigned, but it would have been possible, check it.
            // Maybe subdivision code must be enriched.
            $subdivisionCode = '';
        }

        return new AddressCheckPayload(
            $lang,
            $countryCode,
            $postCode,
            $cityName,
            $streetFull,
            $subdivisionCode
        );
    }
}
