<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Address;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

abstract class AddressExtensionExistsBaseInsurance
{
    public function doEnsure(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        Context $context
    ): void {
        // If it doesn't exist, create a new one with default values
        $addressExtension = $this->createAddressExtensionWithDefaultValues($addressEntity);

        $this->getAddressExtensionRepository()->upsert(
            [
                [
                    'addressId' => $addressExtension->getAddressId(),
                    'amsStatus' => $addressExtension->getAmsStatus(),
                    'amsPredictions' => $addressExtension->getAmsPredictions()
                ]
            ],
            $context
        );

        $this->addExtensionToAddressEntity($addressEntity, $addressExtension);
    }

    abstract protected function createAddressExtensionWithDefaultValues(
        CustomerAddressEntity|OrderAddressEntity $addressEntity
    ): EnderecoBaseAddressExtensionEntity;

    abstract protected function getAddressExtensionRepository(): EntityRepository;

    abstract protected function addExtensionToAddressEntity(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoBaseAddressExtensionEntity $addressExtension
    ): void;
}
