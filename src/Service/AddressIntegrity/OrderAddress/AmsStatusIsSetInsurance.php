<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\AmsStatusIsSetBaseInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class AmsStatusIsSetInsurance extends AmsStatusIsSetBaseInsurance implements IntegrityInsurance
{
    private EnderecoService $enderecoService;
    private EntityRepository $addressExtensionRepository;
    private AddressCacheInterface $addressEntityCache;

    public function __construct(
        IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker,
        EnderecoService $enderecoService,
        EntityRepository $addressExtensionRepository,
        AddressCacheInterface $addressEntityCache
    ) {
        parent::__construct($isAmsRequestPayloadIsUpToDateChecker);

        $this->enderecoService = $enderecoService;
        $this->addressExtensionRepository = $addressExtensionRepository;
        $this->addressEntityCache = $addressEntityCache;
    }

    public static function getPriority(): int
    {
        return -20;
    }

    /**
     * Ensures that address status is still valid.
     *
     * This method checks whether a new status is needed for the address. If so, the data of the address extension
     * will be reset to indicate that it's no longer valid.
     */
    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure($addressEntity, $addressExtension, $salesChannelId, $context);
    }

    protected function isValidationRequired(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        string $salesChannelId,
    ): bool {
        $this->checkAddressEntityType($addressEntity);
        /** @var OrderAddressEntity $addressEntity */

        // Determine if existing order address check is required
        return $this->enderecoService->isExistingAddressCheckFeatureEnabled($salesChannelId)
        && !$this->enderecoService->isAddressRecent($addressEntity);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function reValidateAddress(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        string $salesChannelId,
        Context $context
    ): void {
        $this->checkAddressExtensionType($addressExtension);
        /** @var EnderecoOrderAddressExtensionEntity $addressExtension */

        $data = $addressExtension->resetAndCreateDataForPersistence();
        $this->addressExtensionRepository->upsert([$data], $context);

        $this->addressEntityCache->set($addressEntity);
    }

    private function checkAddressEntityType(CustomerAddressEntity|OrderAddressEntity $addressEntity): void
    {
        if (!$addressEntity instanceof OrderAddressEntity) {
            throw new \InvalidArgumentException(
                sprintf('The address entity must be an instance of %s', OrderAddressEntity::class)
            );
        }
    }

    private function checkAddressExtensionType(
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension
    ): void {
        if (!$addressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            throw new \InvalidArgumentException(
                sprintf('The address extension must be an instance of %s', EnderecoOrderAddressExtensionEntity::class)
            );
        }
    }
}
