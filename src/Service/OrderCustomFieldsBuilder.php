<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

final class OrderCustomFieldsBuilder implements OrderCustomFieldsBuilderInterface
{
    public function buildOrderAddressValidationData(OrderEntity $orderEntity): array
    {
        $orderAddressExtensionCollection = new EnderecoOrderAddressExtensionCollection();

        foreach ($orderEntity->getAddresses() ?? [] as $orderAddressEntity) {
            $this->addOrderAddressExtensionToCollection($orderAddressEntity, $orderAddressExtensionCollection);
        }

        foreach ($orderEntity->getDeliveries() ?? [] as $deliveryEntity) {
            $shippingOrderAddress = $deliveryEntity->getShippingOrderAddress();
            if ($shippingOrderAddress instanceof OrderAddressEntity) {
                $this->addOrderAddressExtensionToCollection(
                    $shippingOrderAddress,
                    $orderAddressExtensionCollection
                );
            }
        }

        return $orderAddressExtensionCollection->buildDataForOrderCustomField();
    }

    private function addOrderAddressExtensionToCollection(
        OrderAddressEntity $orderAddressEntity,
        EnderecoOrderAddressExtensionCollection $orderAddressExtensionCollection
    ): void {
        $orderAddressExtension = $orderAddressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);
        if ($orderAddressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            $orderAddressExtensionCollection->set(
                $orderAddressExtension->getAddressId(),
                $orderAddressExtension
            );
        }
    }
}
