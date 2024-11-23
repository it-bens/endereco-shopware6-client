<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Check;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

interface IsAmsRequestPayloadIsUpToDateCheckerInterface
{
    /**
     * Determines if the persisted AMS request payload of the EnderecoAddressExtension
     * matches the persisted data of the AddressEntity.
     *
     * If the AMS status is "not-checked", true is returned because no validation has been performed yet.
     */
    public function checkIfAmsRequestPayloadItUpToDate(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        Context $context
    ): bool;
}
