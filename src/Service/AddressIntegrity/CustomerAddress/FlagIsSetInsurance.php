<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

abstract class FlagIsSetInsurance
{
    private EntityRepository $customerRepository;
    private EntityRepository $addressExtensionRepository;

    public function __construct(
        EntityRepository $customerRepository,
        EntityRepository $addressExtensionRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressExtensionRepository = $addressExtensionRepository;
    }

    protected function doEnsure(
        CustomerAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity $addressExtension,
        string $customField,
        string $flagProperty,
        Context $context
    ): void {
        $customerId = $addressEntity->getCustomerId();
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context)->first();
        if (!$customer instanceof CustomerEntity) {
            throw new \RuntimeException('Customer not found');
        }

        /** @var  array<mixed>|null $customerCustomFields */
        $customerCustomFields = $customer->getCustomFields();
        $flagValue = isset($customerCustomFields[$customField]);

        $this->addressExtensionRepository->upsert(
            [
                [
                    'addressId' => $addressEntity->getId(),
                    $flagProperty => $flagValue,
                ]
            ],
            $context
        );

        $this->setFlag($addressExtension, $flagValue);
    }

    abstract protected function setFlag(EnderecoCustomerAddressExtensionEntity $addressExtension, bool $value): void;
}
