<?php

namespace Endereco\Shopware6Client\Subscriber;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionDefinition;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderAddressExtensionWrittenSubscriber implements EventSubscriberInterface
{
    private OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater;

    public function __construct(
        OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater
    ) {
        $this->ordersCustomFieldsUpdater = $ordersCustomFieldsUpdater;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.written' => 'updateOrderCustomFields'
        ];
    }

    public function updateOrderCustomFields(EntityWrittenEvent $event): void
    {
        if ($event->getEntityName() !== EnderecoOrderAddressExtensionDefinition::ENTITY_NAME) {
            return;
        }

        // The primary key aka ID of the `EnderecoOrderAddressExtension` is the ID of the `OrderAddress`.
        $orderAddressIds = $event->getIds();

        $this->ordersCustomFieldsUpdater->updateOrdersCustomFields([], $orderAddressIds, $event->getContext());
    }
}
