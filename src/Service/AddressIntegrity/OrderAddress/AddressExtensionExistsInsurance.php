<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\EnderecoBaseAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\AddressExtensionExistsBaseInsurance;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class AddressExtensionExistsInsurance extends AddressExtensionExistsBaseInsurance implements IntegrityInsurance
{
    private EntityRepository $addressExtensionRepository;

    public function __construct(
        EntityRepository $addressExtensionRepository,
    ) {
        $this->addressExtensionRepository = $addressExtensionRepository;
    }

    public static function getPriority(): int
    {
        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        // Check if the address has an 'enderecoAddress' extension
        $addressExtension = $addressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);

        if ($addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            return;
        }

        $this->doEnsure($addressEntity, $context);
    }

    protected function createAddressExtensionWithDefaultValues(
        CustomerAddressEntity|OrderAddressEntity $addressEntity
    ): EnderecoBaseAddressExtensionEntity {
        $this->checkAddressEntityType($addressEntity);
        /** @var OrderAddressEntity $addressEntity */

        $addressExtension = new EnderecoOrderAddressExtensionEntity();
        $addressExtension->setAddressId($addressEntity->getId());
        $addressExtension->setAddress($addressEntity);

        return $addressExtension;
    }

    protected function getAddressExtensionRepository(): EntityRepository
    {
        return $this->addressExtensionRepository;
    }

    protected function addExtensionToAddressEntity(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoBaseAddressExtensionEntity $addressExtension
    ): void {
        $addressEntity->addExtension(OrderAddressExtension::ENDERECO_EXTENSION, $addressExtension);
    }

    private function checkAddressEntityType(CustomerAddressEntity|OrderAddressEntity $addressEntity): void
    {
        if (!$addressEntity instanceof OrderAddressEntity) {
            throw new \InvalidArgumentException(
                sprintf('The address entity must be an instance of %s', OrderAddressEntity::class)
            );
        }
    }
}
