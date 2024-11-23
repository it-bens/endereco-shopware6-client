<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateChecker;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredChecker;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(IsAmsRequestPayloadIsUpToDateChecker::class)
        ->args([
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
        ]);
    $services->alias(IsAmsRequestPayloadIsUpToDateCheckerInterface::class, IsAmsRequestPayloadIsUpToDateChecker::class);

    $services->set(IsStreetSplitRequiredChecker::class)
        ->args([
            '$enderecoService' => service(EnderecoService::class),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
        ]);
    $services->alias(IsStreetSplitRequiredCheckerInterface::class, IsStreetSplitRequiredChecker::class);
};
