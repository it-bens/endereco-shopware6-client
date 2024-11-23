<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionDefinition;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressExtensionExistsInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsRequestPayloadIsUpToDateInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AmsStatusIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\AmazonFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\PayPalExpressFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\IntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsurance;
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
        ->instanceof(IntegrityInsurance::class)
        ->tag('endereco.shopware6_client.customer_address_integrity_insurance');

    $services->set(AddressExtensionExistsInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(AmsRequestPayloadIsUpToDateInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(AmsStatusIsSetInsurance::class)
        ->args([
            '$isAmsRequestPayloadIsUpToDateChecker' => service(IsAmsRequestPayloadIsUpToDateCheckerInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressEntityCache' => service(AddressCacheInterface::class),
        ]);

    $services->set(StreetIsSplitInsurance::class)
        ->args([
            '$isStreetSplitRequiredChecker' => service(IsStreetSplitRequiredCheckerInterface::class),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$enderecoService' => service(EnderecoService::class),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(AmazonFlagIsSetInsurance::class)
        ->args([
            '$customerRepository' => service('customer.repository'),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    $services->set(PayPalExpressFlagIsSetInsurance::class)
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

    $services->set(CustomerAddressIntegrityInsurance::class)
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
        CustomerAddressIntegrityInsurance::class
    );
};
