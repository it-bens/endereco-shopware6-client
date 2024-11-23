<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionDefinition;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateChecker;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredChecker;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressExtensionExistsInsurance
    as CustomerAddressExtensionExistsInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsRequestPayloadIsUpToDateInsurance
    as CustomerAddressAmsRequestPayloadIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsStatusIsSetInsurance
    as CustomerAddressAmsStatusIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\AmazonFlagIsSetInsurance
    as CustomerAddressAmazonFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\PayPalExpressFlagIsSetInsurance
    as CustomerAddressPayPalExpressFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\IntegrityInsurance
    as CustomerAddressIntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance
    as CustomerAddressStreetIsSplitInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsuranceInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\CustomerAddressSyncer;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\CustomerAddressSyncerInterface;
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
        ->instanceof(CustomerAddressIntegrityInsurance::class)
        ->tag('endereco.shopware6_client.customer_address_integrity_insurance');

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

    $services->set(CustomerAddressExtensionExistsInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(CustomerAddressAmsRequestPayloadIsSetInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(CustomerAddressAmsStatusIsSetInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressEntityCache' => service(AddressCacheInterface::class),
        ]);

    $services->set(CustomerAddressStreetIsSplitInsurance::class)
        ->args([
            '$isStreetSplitRequiredChecker' => service(IsStreetSplitRequiredCheckerInterface::class),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(CustomerAddressAmazonFlagIsSetInsurance::class)
        ->args([
            '$customerRepository' => service('customer.repository'),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(CustomerAddressPayPalExpressFlagIsSetInsurance::class)
        ->args([
            '$customerRepository' => service('customer.repository'),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(CustomerAddressSyncer::class)
        ->args([
            '$addressCache' => service(AddressCacheInterface::class),
        ]);
    $services->alias(CustomerAddressSyncerInterface::class, CustomerAddressSyncer::class);

    $services->set(\Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsurance::class)
        ->args([
            '$addressCache' => service(AddressCacheInterface::class),
            '$addressSyncer' => service(CustomerAddressSyncerInterface::class),
            '$insurances' => tagged_iterator(
                'endereco.shopware6_client.customer_address_integrity_insurance',
                null,
                null,
                'getPriority'
            )
        ]);
    $services->alias(
        CustomerAddressIntegrityInsuranceInterface::class,
        \Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsurance::class
    );
};
