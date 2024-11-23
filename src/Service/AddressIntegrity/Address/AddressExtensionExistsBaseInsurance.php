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
        $this->getAddressExtensionRepository()->upsert(
            [
                [
                    'addressId' => $addressEntity->getId(),
                    'amsStatus' => EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED,
                    'amsPredictions' => []
                ]
            ],
            $context
        );
    }

    abstract protected function getAddressExtensionRepository(): EntityRepository;
}
