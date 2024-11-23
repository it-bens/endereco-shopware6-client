<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Address;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

abstract class AmsStatusIsSetBaseInsurance
{
    private IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker;

    protected function __construct(IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker)
    {
        $this->isAmsRequestPayloadIsUpToDateChecker = $isAmsRequestPayloadIsUpToDateChecker;
    }

    protected function doEnsure(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        string $salesChannelId,
        Context $context
    ): void {
        $isValidationRequired = $this->isValidationRequired($addressEntity, $salesChannelId);
        if (!$isValidationRequired) {
            return;
        }

        $isRequestPayloadUpToDate = $this->isAmsRequestPayloadIsUpToDateChecker->checkIfAmsRequestPayloadItUpToDate(
            $addressEntity,
            $addressExtension,
            $context
        );
        $isReValidationRequired = !$isRequestPayloadUpToDate;
        if (!$isReValidationRequired) {
            return;
        }

        $this->reValidateAddress($addressEntity, $addressExtension, $salesChannelId, $context);
    }

    abstract protected function isValidationRequired(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        string $salesChannelId,
    ): bool;

    abstract protected function reValidateAddress(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        string $salesChannelId,
        Context $context
    ): void;
}
