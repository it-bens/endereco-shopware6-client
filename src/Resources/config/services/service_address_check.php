<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilder;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcher;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryHasStatesChecker;
use Endereco\Shopware6Client\Service\AddressCheck\CountryHasStatesCheckerInterface;
use Endereco\Shopware6Client\Service\AddressCheck\LocaleFetcher;
use Endereco\Shopware6Client\Service\AddressCheck\LocaleFetcherInterface;
use Endereco\Shopware6Client\Service\AddressCheck\SubdivisionCodeFetcher;
use Endereco\Shopware6Client\Service\AddressCheck\SubdivisionCodeFetcherInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(AddressCheckPayloadBuilder::class)
        ->args([
            '$localeFetcher' => service(LocaleFetcherInterface::class),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$subdivisionCodeFetcher' => service(SubdivisionCodeFetcherInterface::class),
            '$countryHasStatesChecker' => service(CountryHasStatesCheckerInterface::class)
        ]);
    $services->alias(AddressCheckPayloadBuilderInterface::class, AddressCheckPayloadBuilder::class);

    $services->set(CountryCodeFetcher::class)
        ->args([
            '$countryRepository' => service('country.repository')
        ]);
    $services->alias(CountryCodeFetcherInterface::class, CountryCodeFetcher::class);

    $services->set(CountryHasStatesChecker::class)
        ->args([
            '$countryRepository' => service('country.repository')
        ]);
    $services->alias(CountryHasStatesCheckerInterface::class, CountryHasStatesChecker::class);

    $services->set(LocaleFetcher::class)
        ->args([
            '$salesChannelDomainRepository' => service('sales_channel_domain.repository')
        ]);
    $services->alias(LocaleFetcherInterface::class, LocaleFetcher::class);

    $services->set(SubdivisionCodeFetcher::class)
        ->args([
            '$countryStateRepository' => service('country_state.repository')
        ]);
    $services->alias(SubdivisionCodeFetcherInterface::class, SubdivisionCodeFetcher::class);
};
