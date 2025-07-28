<?php

namespace Endereco\Shopware6Client\Subscriber;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionDefinition;
use Endereco\Shopware6Client\Model\ExpectedSystemConfigValue;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

/**
 * Updates order custom fields when Endereco address extensions change
 */
class OrderSubscriber implements EventSubscriberInterface
{
    /** @var OrdersCustomFieldsUpdaterInterface */
    // @phpstan-ignore-next-line UnusedPrivateProperty
    private OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater;
    /** @var EntityRepository */
    // @phpstan-ignore-next-line UnusedPrivateProperty
    private EntityRepository $orderAddressRepository;

    /** @var BySystemConfigFilterInterface */
    // @phpstan-ignore-next-line UnusedPrivateProperty
    private BySystemConfigFilterInterface $bySystemConfigFilter;

    public function __construct(
        EntityRepository $orderAddressRepository,
        BySystemConfigFilterInterface $bySystemConfigFilter,
        OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
        $this->bySystemConfigFilter = $bySystemConfigFilter;
        $this->ordersCustomFieldsUpdater = $ordersCustomFieldsUpdater;
    }

    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [
            EnderecoOrderAddressExtensionDefinition::ENTITY_NAME . '.written' => 'updateOrderCustomFields'
        ];
    }

    /**
     * Updates custom fields when address extensions are written. Customer fields always mirror the content of
     * order address extension, hence .written event that triggers the rewrite.
     *
     * @param EntityWrittenEvent $event
     */
    public function updateOrderCustomFields(EntityWrittenEvent $event): void
    {
        // TODO: Temporarily disabled due to order address handling bug - re-enable after tests pass
        return;

        // @phpstan-ignore-next-line Deadcode.UnreachableStatement
        if ($event->getEntityName() !== EnderecoOrderAddressExtensionDefinition::ENTITY_NAME) {
            return;
        }

        // Extract address IDs from the written extension entities
        $orderAddressIds = $this->extractAddressIdsFromWrittenExtensions($event);

        if (empty($orderAddressIds)) {
            return;
        }

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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $orderAddressIds));

        /** @var OrderAddressCollection $orderAddresses */
        $orderAddresses = $this->orderAddressRepository->search($criteria, $event->getContext())->getEntities();

        $this->ordersCustomFieldsUpdater->updateOrdersCustomFields($orderAddresses, $event->getContext());
    }

    /**
     * Extracts order address IDs from the written extension entities.
     *
     * Since the extension entity now has its own ID as primary key, we need to extract
     * the actual address IDs from the written payloads to identify which order addresses
     * need their custom fields updated.
     *
     * @param EntityWrittenEvent $event
     * @return string[] Array of order address IDs
     */
    // @phpstan-ignore-next-line UnusedPrivateMethod
    private function extractAddressIdsFromWrittenExtensions(EntityWrittenEvent $event): array
    {
        $addressIds = [];
        foreach ($event->getPayloads() as $payload) {
            if (isset($payload['addressId'])) {
                $addressIds[] = $payload['addressId'];
            }
        }

        return array_unique($addressIds);
    }
}
