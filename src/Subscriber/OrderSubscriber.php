<?php

namespace Endereco\Shopware6Client\Subscriber;

use Endereco\Shopware6Client\Model\ExpectedSystemConfigValue;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private EntityRepository $orderRepository;
    private BySystemConfigFilterInterface $bySystemConfigFilter;
    private EntityRepository $orderAddressRepository;
    private OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater;

    public function __construct(
        EntityRepository $orderRepository,
        BySystemConfigFilterInterface $bySystemConfigFilter,
        EntityRepository $orderAddressRepository,
        OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater
    ) {
        $this->orderRepository = $orderRepository;
        $this->bySystemConfigFilter = $bySystemConfigFilter;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->ordersCustomFieldsUpdater = $ordersCustomFieldsUpdater;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_LOADED_EVENT => 'updateOrderCustomFields',
        ];
    }

    public function updateOrderCustomFields(EntityLoadedEvent $event): void
    {
        if ($event->getDefinition()->getEntityName() !== OrderDefinition::ENTITY_NAME) {
            return;
        }

        $orderIds = $event->getIds();

        $orderIds = $this->bySystemConfigFilter->filterEntityIdsBySystemConfig(
            $this->orderRepository,
            'salesChannelId',
            $orderIds,
            [
                new ExpectedSystemConfigValue('enderecoActiveInThisChannel', true),
                new ExpectedSystemConfigValue('enderecoWriteOrderCustomFields', true)
            ],
            $event->getContext()
        );

        $orders = new OrderCollection();
        /** @var OrderEntity $order */
        foreach ($event->getEntities() as $order) {
            if (in_array($order->getId(), $orderIds, true)) {
                $orders->set($order->getId(), $order);
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('orderId', $orders->getIds()));
        /** @var OrderAddressCollection $orderAddresses */
        $orderAddresses = $this->orderAddressRepository->search($criteria, $event->getContext())->getEntities();

        $this->ordersCustomFieldsUpdater->updateOrdersCustomFields(
            $orders,
            $orderAddresses,
            $event->getContext()
        );
    }
}
