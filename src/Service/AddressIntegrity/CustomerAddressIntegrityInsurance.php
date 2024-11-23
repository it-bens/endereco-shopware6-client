<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity;

use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\IntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\CustomerAddressSyncer;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\CustomerAddressSyncerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

final class CustomerAddressIntegrityInsurance implements CustomerAddressIntegrityInsuranceInterface
{
    private AddressCacheInterface $addressCache;
    private CustomerAddressSyncerInterface $addressSyncer;
    /** @var iterable<IntegrityInsurance> */
    private iterable $insurances;

    /**
     * @param AddressCacheInterface $addressCache
     * @param CustomerAddressSyncer $addressSyncer
     * @param iterable<IntegrityInsurance> $insurances
     */
    public function __construct(
        AddressCacheInterface $addressCache,
        CustomerAddressSyncerInterface $addressSyncer,
        iterable $insurances
    ) {
        $this->addressCache = $addressCache;
        $this->addressSyncer = $addressSyncer;
        $this->insurances = $insurances;
    }

    public function ensure(CustomerAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        // IF the address has been processed already, we can be sure the database has all the information
        // So we just sync the entity with this information.
        if ($this->addressCache->has($addressEntity->getId())) {
            $this->addressSyncer->syncCustomerAddressEntity($addressEntity);

            return;
        }

        foreach ($this->insurances as $insurance) {
            $insurance->ensure($addressEntity, $salesChannelId, $context);
        }

        $this->addressCache->set($addressEntity);
    }
}
