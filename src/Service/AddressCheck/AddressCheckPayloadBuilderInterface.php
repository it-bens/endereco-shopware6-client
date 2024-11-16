<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Endereco\Shopware6Client\Model\AddressCheckPayload;
use Endereco\Shopware6Client\Model\AddressCheckData;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
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

    /**
     * @param CustomerAddressEntity|OrderAddressEntity $addressEntity
     * @param Context $context
     *
     * @return AddressCheckData
     */
    public function buildAddressCheckPayloadWithoutLanguage(
        $addressEntity,
        Context $context
    ): AddressCheckData;
}
