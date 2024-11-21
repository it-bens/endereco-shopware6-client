<?php

namespace Endereco\Shopware6Client\Subscriber;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionDefinition;
use Endereco\Shopware6Client\Model\ExpectedSystemConfigValue;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderAddressExtensionWrittenSubscriber implements EventSubscriberInterface
{
    private EntityRepository $orderAddressRepository;
    private BySystemConfigFilterInterface $bySystemConfigFilter;
    private OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater;

    public function __construct(
        EntityRepository $orderAddressRepository,
        BySystemConfigFilterInterface $bySystemConfigFilter,
        OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
        $this->bySystemConfigFilter = $bySystemConfigFilter;
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

        $orderAddressIds = $this->bySystemConfigFilter->filterEntityIdsBySystemConfig(
            $this->orderAddressRepository,
            'order.salesChannelId',
            $orderAddressIds,
            [
                new ExpectedSystemConfigValue('enderecoActiveInThisChannel', true),
                new ExpectedSystemConfigValue('enderecoWriteOrderCustomFields', true)
            ],
            $event->getContext()
        );

        $this->ordersCustomFieldsUpdater->updateOrdersCustomFields([], $orderAddressIds, $event->getContext());
    }
}
