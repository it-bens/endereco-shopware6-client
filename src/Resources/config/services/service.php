<?php

/**
 * Core service configuration for Endereco integration
 */

declare(strict_types=1);

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionDefinition;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionDefinition;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcher;
use Endereco\Shopware6Client\Service\AddressCheck\CountryHasStatesChecker;
use Endereco\Shopware6Client\Service\AddressCheck\LocaleFetcher;
use Endereco\Shopware6Client\Service\AddressCheck\SubdivisionCodeFetcher;
use Endereco\Shopware6Client\Service\AddressCorrection\StreetSplitter;
use Endereco\Shopware6Client\Service\AddressCorrection\StreetSplitterInterface;
use Endereco\Shopware6Client\Service\AddressCorrection\StreetSplitterWithCache;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\AddressExtensionExistsInsurance as CustomerAddressExtensionExistsInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\AmazonFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance\PayPalExpressFlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\IntegrityInsurance as CustomerAddressIntegrityInsurancePolicy;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\AddressExtensionExistsInsurance as OrderAddressExtensionExistsInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\IntegrityInsurance as OrderAddressIntegrityInsurancePolicy;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddressIntegrityInsurance;
use Endereco\Shopware6Client\Service\EnderecoService;
use Endereco\Shopware6Client\Service\EnderecoService\PluginVersionFetcher;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdater;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    /**
     * Tags services that implement integrity checks.
     * Allows for automatic collection and execution of all integrity insurances.
     */
    $services
        ->instanceof(CustomerAddressIntegrityInsurancePolicy::class)
        ->tag('endereco.shopware6_client.customer_address_integrity_insurance');
    $services
        ->instanceof(OrderAddressIntegrityInsurancePolicy::class)
        ->tag('endereco.shopware6_client.order_address_integrity_insurance');

    $services->load('Endereco\\Shopware6Client\\Service\\', __DIR__ . '/../../../Service');

    $services->set(EnderecoService::class)
        ->args([
            '$countryStateRepository' => service('country_state.repository'),
            '$customerAddressRepository' => service('customer_address.repository'),
            '$orderAddressRepository' => service('order_address.repository'),
            '$requestStack' => service('request_stack'),
            '$logger' => service('Endereco\Shopware6Client\Run\Logger'),
        ])
        ->public();

    $services->set(OrdersCustomFieldsUpdater::class)
        ->args([
            '$orderRepository' => service('order.repository'),
        ]);

    /**
     * Retrieves ISO country codes from Shopware country entities.
     * Essential for standardized country identification in API calls.
     */
    $services->set(CountryCodeFetcher::class)
        ->args([
            '$countryRepository' => service('country.repository')
        ]);

    /**
     * Verifies if a country uses state/province subdivisions.
     * Helps determine if state/province data should be included in validation.
     */
    $services->set(CountryHasStatesChecker::class)
        ->args([
            '$countryRepository' => service('country.repository')
        ]);

    /**
     * Fetches locale information from sales channel domains. Currently not really needed.
     */
    $services->set(LocaleFetcher::class)
        ->args([
            '$salesChannelDomainRepository' => service('sales_channel_domain.repository')
        ]);

    /**
     * Retrieves standardized codes for states/provinces.
     * Ensures consistent subdivision identification in API requests.
     */
    $services->set(SubdivisionCodeFetcher::class)
        ->args([
            '$countryStateRepository' => service('country_state.repository')
        ]);

    $services->set(StreetSplitter::class)
        ->args([
            '$logger' => service('Endereco\Shopware6Client\Run\Logger'),
        ]);
    $services->set(StreetSplitterWithCache::class)
        ->args([
            '$cache' => service('endereco_service_cache'),
            '$streetSplitter' => service(StreetSplitter::class),
        ]);
    $services->alias(StreetSplitterInterface::class, StreetSplitterWithCache::class);

    /**
     * Ensures the address extension entity exists both in the entity and the database.
     * This insurance creates or verifies the EnderecoCustomerAddressExtension record as necessary.
     */
    $services->set(CustomerAddressExtensionExistsInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    /**
     * Ensures that the Shopware "street" field is properly split into "street" and "housenumber" in the extension.
     * This is crucial for customers that require explicit separate street and house number fields.
     */
    $services->set(StreetIsSplitInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    /**
     * Sets an Amazon Pay flag in the extension if the address originates from an Amazon Pay checkout process.
     */
    $services->set(AmazonFlagIsSetInsurance::class)
        ->args([
            '$customerRepository' => service('customer.repository'),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    /**
     * Sets a PayPal Express flag in the extension if the address originates from a PayPal Express checkout process.
     */
    $services->set(PayPalExpressFlagIsSetInsurance::class)
        ->args([
            '$customerRepository' => service('customer.repository'),
            '$addressExtensionRepository' => service(
                EnderecoCustomerAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    /**
     * Coordinates all other insurances to ensure that the address data is in the correct form
     * and contains all necessary information (e.g., extension data, valid street data).
     *
     * The tagged_iterator collects all services tagged with
     * 'endereco.shopware6_client.customer_address_integrity_insurance' and runs them in order.
     */
    $services->set(CustomerAddressIntegrityInsurance::class)
        ->args([
            '$insurances' => tagged_iterator(
                'endereco.shopware6_client.customer_address_integrity_insurance',
                null,
                null,
                'getPriority'
            )
        ]);

    /**
     * Fetches the plugin version from the plugin repository.
     */
    $services->set(PluginVersionFetcher::class)
        ->args([
            '$pluginRepository' => service('plugin.repository')
        ]);

    /**
     * Ensures existence of address extension records.
     * Creates missing extension entities as needed.
     */
    $services->set(OrderAddressExtensionExistsInsurance::class)
        ->args([
            '$addressExtensionRepository' => service(
                EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.repository'
            ),
        ]);

    /**
     * Coordinates all integrity checks and maintenance.
     * Orchestrates the execution of individual integrity insurances.
     */
    $services->set(OrderAddressIntegrityInsurance::class)
        ->args([
            '$insurances' => tagged_iterator(
                'endereco.shopware6_client.order_address_integrity_insurance',
                null,
                null,
                'getPriority'
            )
        ]);
};
