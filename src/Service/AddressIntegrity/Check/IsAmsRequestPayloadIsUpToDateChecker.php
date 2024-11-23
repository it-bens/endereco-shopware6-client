<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Check;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

final class IsAmsRequestPayloadIsUpToDateChecker implements IsAmsRequestPayloadIsUpToDateCheckerInterface
{
    private AddressCheckPayloadBuilderInterface $addressCheckPayloadBuilder;

    public function __construct(AddressCheckPayloadBuilderInterface $addressCheckPayloadBuilder)
    {
        $this->addressCheckPayloadBuilder = $addressCheckPayloadBuilder;
    }

    public function checkIfAmsRequestPayloadItUpToDate(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        Context $context
    ): bool {
        if ($addressExtension->getAmsStatus() === EnderecoBaseAddressExtensionEntity::AMS_STATUS_NOT_CHECKED) {
            return true;
        }

        $amsRequestPayload = $this->addressCheckPayloadBuilder->buildAddressCheckPayloadWithoutLanguage(
            $addressEntity,
            $context
        );
        $amsRequestPayloadData = $amsRequestPayload->data();

        $persistedAmsRequestPayload = $addressExtension->getAmsRequestPayload();

        return crc32(serialize($amsRequestPayloadData)) === crc32(serialize($persistedAmsRequestPayload));
    }
}
