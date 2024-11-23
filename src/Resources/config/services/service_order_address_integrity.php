<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionDefinition;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\AddressExtensionExistsInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\AmsRequestPayloadIsUpToDateInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\AmsStatusIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\IntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\StreetIsSplitInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddressIntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddressIntegrityInsuranceInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\OrderAddressSyncer;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\OrderAddressSyncerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->instanceof(IntegrityInsurance::class)
        ->tag('endereco.shopware6_client.order_address_integrity_insurance');

    $services->set(AddressExtensionExistsInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(AmsRequestPayloadIsUpToDateInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
            '$addressExtensionRepository' => service(
                EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(AmsStatusIsSetInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressExtensionRepository' => service(
                EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
            '$addressEntityCache' => service(AddressCacheInterface::class),
        ]);

    $services->set(StreetIsSplitInsurance::class)
        ->args([
            '$isStreetSplitRequiredChecker' => service(IsStreetSplitRequiredCheckerInterface::class),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressExtensionRepository' => service(
                EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(OrderAddressSyncer::class)
        ->args([
            '$addressCache' => service(AddressCacheInterface::class),
        ]);
    $services->alias(OrderAddressSyncerInterface::class, OrderAddressSyncer::class);

    $services->set(OrderAddressIntegrityInsurance::class)
        ->args([
            '$addressCache' => service(AddressCacheInterface::class),
            '$addressSyncer' => service(OrderAddressSyncerInterface::class),
            '$insurances' => tagged_iterator(
                'endereco.shopware6_client.order_address_integrity_insurance',
                null,
                null,
                'getPriority'
            )
        ]);
    $services->alias(
        OrderAddressIntegrityInsuranceInterface::class,
        OrderAddressIntegrityInsurance::class
    );
};
