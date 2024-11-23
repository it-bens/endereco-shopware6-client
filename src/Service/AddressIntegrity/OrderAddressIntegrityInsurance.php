<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity;

use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress\IntegrityInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Sync\OrderAddressSyncerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

final class OrderAddressIntegrityInsurance implements OrderAddressIntegrityInsuranceInterface
{
    private AddressCacheInterface $addressCache;
    private OrderAddressSyncerInterface $addressSyncer;
    /** @var iterable<IntegrityInsurance> */
    private iterable $insurances;

    /**
     * @param AddressCacheInterface $addressCache
     * @param OrderAddressSyncerInterface $addressSyncer
     * @param iterable<IntegrityInsurance> $insurances
     */
    public function __construct(
        AddressCacheInterface $addressCache,
        OrderAddressSyncerInterface $addressSyncer,
        iterable $insurances
    ) {
        $this->addressCache = $addressCache;
        $this->addressSyncer = $addressSyncer;
        $this->insurances = $insurances;
    }

    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        // IF the address has been processed already, we can be sure the database has all the information
        // So we just sync the entity with this information.
        if ($this->addressCache->has($addressEntity->getId())) {
            $this->addressSyncer->syncOrderAddressEntity($addressEntity);

            return;
        }

        foreach ($this->insurances as $insurance) {
            $insurance->ensure($addressEntity, $salesChannelId, $context);
        }

        $this->addressCache->set($addressEntity);
    }
}
