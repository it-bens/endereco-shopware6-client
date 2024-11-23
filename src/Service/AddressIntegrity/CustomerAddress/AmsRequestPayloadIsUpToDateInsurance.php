<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\AmsRequestPayloadIsUpToDateBaseInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class AmsRequestPayloadIsUpToDateInsurance extends AmsRequestPayloadIsUpToDateBaseInsurance implements IntegrityInsurance
{
    private EntityRepository $addressExtensionRepository;

    public function __construct(
        IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker,
        AddressCheckPayloadBuilderInterface $addressCheckPayloadBuilder,
        EntityRepository $addressExtensionRepository
    ) {
        parent::__construct($isAmsRequestPayloadIsUpToDateChecker, $addressCheckPayloadBuilder);

        $this->addressExtensionRepository = $addressExtensionRepository;
    }

    public static function getPriority(): int
    {
        return -30;
    }

    /**
     * Ensures that the AMS request payload of the EnderecoAddressExtension is update-to-date with the data
     * in the AddressEntity and updates the AMS request payload if necessary.
     *
     * If the AMS status is "not-checked", the AMS request payload won't be updated.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ensure(CustomerAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure($addressEntity, $addressExtension, $context);
    }

    protected function getAddressExtensionRepository(): EntityRepository
    {
        return $this->addressExtensionRepository;
    }
}
