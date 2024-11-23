<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressesExtensionAmsRequestPayloadUpdaterInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddressIntegrityInsuranceInterface;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Endereco\Shopware6Client\Service\OrderAddressToCustomerAddressDataMatcherInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Endereco\Shopware6Client\Subscriber\AddressExtensionWrittenSubscriber;
use Endereco\Shopware6Client\Subscriber\ConvertCartToOrderSubscriber;
use Endereco\Shopware6Client\Subscriber\CustomerAddressSubscriber;
use Endereco\Shopware6Client\Subscriber\OrderAddressExtensionWrittenSubscriber;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(AddressExtensionWrittenSubscriber::class)
        ->args([
            '$addressesExtensionAmsRequestPayloadUpdater'
                => service(AddressesExtensionAmsRequestPayloadUpdaterInterface::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ConvertCartToOrderSubscriber::class)
        ->args([
            '$orderAddressToCustomerAddressDataMatcher'
                => service(OrderAddressToCustomerAddressDataMatcherInterface::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(CustomerAddressSubscriber::class)
        ->args([
            '$systemConfigService' => service(SystemConfigService::class),
            '$enderecoService' => service(EnderecoService::class),
            '$customerRepository' => service('customer.repository'),
            '$customerAddressRepository' => service('customer_address.repository'),
            '$enderecoAddressExtensionRepository' => service('endereco_customer_address_ext.repository'),
            '$countryRepository' => service('country.repository'),
            '$countryStateRepository' => service('country_state.repository'),
            '$countryCodeFetcher' => service(CountryCodeFetcherInterface::class),
            '$customerAddressIntegrityInsurance' => service(CustomerAddressIntegrityInsuranceInterface::class),
            '$requestStack' => service('request_stack'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(OrderAddressExtensionWrittenSubscriber::class)
        ->args([
            '$orderAddressRepository' => service('order_address.repository'),
            '$bySystemConfigFilter' => service(BySystemConfigFilterInterface::class),
            '$ordersCustomFieldsUpdater' => service(OrdersCustomFieldsUpdaterInterface::class),
        ])
        ->tag('kernel.event_subscriber');
};
