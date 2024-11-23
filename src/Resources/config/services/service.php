<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Service\AddDataToPage;
use Endereco\Shopware6Client\Service\AddressCache;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressesExtensionAmsRequestPayloadUpdater;
use Endereco\Shopware6Client\Service\AddressesExtensionAmsRequestPayloadUpdaterInterface;
use Endereco\Shopware6Client\Service\BySystemConfigFilter;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Endereco\Shopware6Client\Service\OrderAddressToCustomerAddressDataMatcher;
use Endereco\Shopware6Client\Service\OrderAddressToCustomerAddressDataMatcherInterface;
use Endereco\Shopware6Client\Service\OrderCustomFieldsBuilder;
use Endereco\Shopware6Client\Service\OrderCustomFieldsBuilderInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdater;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(AddDataToPage::class)
        ->args([
            '$systemConfigService' => service(SystemConfigService::class),
            '$enderecoService' => service(EnderecoService::class),
            '$countryRepository' => service('country.repository'),
            '$stateRepository' => service('country_state.repository'),
            '$salutationRepository' => service('salutation.repository'),
            '$pluginRepository' => service('plugin.repository')
        ])
        ->tag('kernel.event_subscriber');

    $services->set(AddressCache::class);
    $services->alias(AddressCacheInterface::class, AddressCache::class);

    $services->set(AddressesExtensionAmsRequestPayloadUpdater::class)
        ->args([
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
            '$customerAddressExtensionRepository' => service('endereco_customer_address_ext.repository'),
            '$orderAddressExtensionRepository' => service('endereco_order_address_ext.repository'),
        ]);
    $services->alias(
        AddressesExtensionAmsRequestPayloadUpdaterInterface::class,
        AddressesExtensionAmsRequestPayloadUpdater::class
    );

    $services->set(BySystemConfigFilter::class)
        ->args([
            '$systemConfigService' => service(SystemConfigService::class)
        ]);
    $services->alias(BySystemConfigFilterInterface::class, BySystemConfigFilter::class);

    $services->set(EnderecoService::class)
        ->args([
            '$systemConfigService' => service(SystemConfigService::class),
            '$pluginRepository' => service('plugin.repository'),
            '$countryStateRepository' => service('country_state.repository'),
            '$customerAddressRepository' => service('customer_address.repository'),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$addressCheckPayloadBuilder' => service(AddressCheckPayloadBuilderInterface::class),
            '$requestStack' => service('request_stack'),
            '$logger' => service('Endereco\Shopware6Client\Run\Logger'),
        ])
        ->public();

    $services->set(OrderAddressToCustomerAddressDataMatcher::class);
    $services->alias(
        OrderAddressToCustomerAddressDataMatcherInterface::class,
        OrderAddressToCustomerAddressDataMatcher::class
    );

    $services->set(OrderCustomFieldsBuilder::class);
    $services->alias(OrderCustomFieldsBuilderInterface::class, OrderCustomFieldsBuilder::class);

    $services->set(OrdersCustomFieldsUpdater::class)
        ->args([
            '$orderCustomFieldBuilder' => service(OrderCustomFieldsBuilderInterface::class),
            '$orderRepository' => service('order.repository')
        ]);
    $services->alias(OrdersCustomFieldsUpdaterInterface::class, OrdersCustomFieldsUpdater::class);
};
