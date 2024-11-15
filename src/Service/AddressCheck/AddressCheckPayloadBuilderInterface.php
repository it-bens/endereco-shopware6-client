<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Endereco\Shopware6Client\Model\AddressCheckPayload;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

interface AddressCheckPayloadBuilderInterface
{
    /**
     * @param string $salesChannelId
     * @param CustomerAddressEntity $addressEntity
     * @param Context $context
     *
     * @return AddressCheckPayload
     */
    public function buildAddressCheckPayload(
        string $salesChannelId,
        CustomerAddressEntity $addressEntity,
        Context $context
    ): AddressCheckPayload;
}
