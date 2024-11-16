<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Endereco\Shopware6Client\Model\AddressCheckPayload;
use Endereco\Shopware6Client\Model\AddressCheckData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
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
        $lang = $this->getLang($salesChannelId, $context);
        $countryCode = $this->getCountryCode($addressEntity, $context);
        $postCode = $this->getPostCode($addressEntity);
        $cityName = $this->getCityName($addressEntity);
        $streetFull = $this->getStreetFull($addressEntity);
        $subdivisionCode = $this->getSubdivisionCode($addressEntity, $context);

        return new AddressCheckPayload(
            $lang,
            $countryCode,
            $postCode,
            $cityName,
            $streetFull,
            $subdivisionCode
        );
    }

    public function buildAddressCheckPayloadWithoutLanguage(
        $addressEntity,
        Context $context
    ): AddressCheckData {
        $countryCode = $this->getCountryCode($addressEntity, $context);
        $postCode = $this->getPostCode($addressEntity);
        $cityName = $this->getCityName($addressEntity);
        $streetFull = $this->getStreetFull($addressEntity);
        $subdivisionCode = $this->getSubdivisionCode($addressEntity, $context);

        return new AddressCheckData(
            $countryCode,
            $postCode,
            $cityName,
            $streetFull,
            $subdivisionCode
        );
    }

    private function getLang(string $salesChannelId, Context $context): string
    {
        try {
            return $this->localeFetcher->fetchLocaleBySalesChannelId($salesChannelId, $context);
        } catch (\Exception $e) {
            return 'de'; // set "de" by default.
        }
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @param Context $context
     * @return string
     */
    private function getCountryCode($addressEntity, Context $context): string
    {
        $countryId = $addressEntity->getCountryId();

        return $this->countryCodeFetcher->fetchCountryCodeByCountryIdAndContext($countryId, $context);
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @return string
     */
    private function getPostCode($addressEntity): string
    {
        return empty($addressEntity->getZipcode()) ? '' : $addressEntity->getZipcode();
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @return string
     */
    private function getCityName($addressEntity): string
    {
        return $addressEntity->getCity();
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @return string
     */
    private function getStreetFull($addressEntity): string
    {
        return $addressEntity->getStreet();
    }

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @param Context $context
     * @return string|null
     */
    private function getSubdivisionCode($addressEntity, Context $context): ?string
    {
        if ($addressEntity->getCountryStateId() !== null) {
            $subdivisionCode = $this->subdivisionCodeFetcher->fetchSubdivisionCodeByCountryStateId(
                $addressEntity->getCountryStateId(),
                $context
            );

            if ($subdivisionCode !== null) {
                return $subdivisionCode;
            }
        }

        $countryId = $addressEntity->getCountryId();
        if ($this->countryHasStatesChecker->hasCountryStates($countryId, $context)) {
            // If a state was not assigned, but it would have been possible, check it.
            // Maybe subdivision code must be enriched.
            return '';
        }

        return null;
    }
}
