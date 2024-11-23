<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

interface CustomerAddressIntegrityInsuranceInterface
{
    /**
     * Ensure:
     * the address extension exists,
     * the street is split,
     * the PayPal Express flag is set,
     * the Amazon Pay flag is set,
     * the AMS status is still valid
     * the AMS request payload is up-to-date with the AddressEntity data
     *
     * @param CustomerAddressEntity $addressEntity
     * @param string $salesChannelId
     * @param Context $context
     */
    public function ensure(CustomerAddressEntity $addressEntity, string $salesChannelId, Context $context): void;
}