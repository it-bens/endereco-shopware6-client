<?php

declare(strict_types=1);

use Endereco\Shopware6Client\Console\UpdateOrdersCustomFieldsCommand;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(UpdateOrdersCustomFieldsCommand::class)
        ->args([
            '$orderRepository' => service('order.repository'),
            '$bySystemConfigFilter' => service(BySystemConfigFilterInterface::class),
            '$ordersCustomFieldsUpdater' => service(OrdersCustomFieldsUpdaterInterface::class),
        ])
        ->tag('console.command');
};
