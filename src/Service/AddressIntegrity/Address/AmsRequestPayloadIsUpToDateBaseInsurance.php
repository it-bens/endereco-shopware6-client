<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Address;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

abstract class AmsRequestPayloadIsUpToDateBaseInsurance
{
    private IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker;
    private AddressCheckPayloadBuilderInterface $addressCheckPayloadBuilder;

    public function __construct(
        IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker,
        AddressCheckPayloadBuilderInterface $addressCheckPayloadBuilder,
    ) {
        $this->isAmsRequestPayloadIsUpToDateChecker = $isAmsRequestPayloadIsUpToDateChecker;
        $this->addressCheckPayloadBuilder = $addressCheckPayloadBuilder;
    }

    public function doEnsure(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        Context $context
    ): void {
        $isRequestPayloadIsUpToDate = $this->isAmsRequestPayloadIsUpToDateChecker->checkIfAmsRequestPayloadItUpToDate(
            $addressEntity,
            $addressExtension,
            $context
        );
        if ($isRequestPayloadIsUpToDate) {
            return;
        }

        $addressCheckPayload = $this->addressCheckPayloadBuilder->buildAddressCheckPayloadWithoutLanguage(
            $addressEntity,
            $context
        );

        $this->getAddressExtensionRepository()->update(
            [
                [
                    'addressId' => $addressExtension->getAddressId(),
                    'amsRequestPayload' => $addressCheckPayload->data()
                ]
            ],
            $context
        );
    }

    abstract protected function getAddressExtensionRepository(): EntityRepository;
}
