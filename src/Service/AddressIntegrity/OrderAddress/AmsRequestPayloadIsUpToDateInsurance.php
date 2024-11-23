<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Endereco\Shopware6Client\Service\AddressCheck\AddressCheckPayloadBuilderInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\AmsRequestPayloadIsUpToDateBaseInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
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
    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure($addressEntity, $addressExtension, $context);
    }

    protected function getAddressExtensionRepository(): EntityRepository
    {
        return $this->addressExtensionRepository;
    }
}
