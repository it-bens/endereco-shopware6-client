<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Sync;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;

interface OrderAddressSyncerInterface
{
    /**
     * When the order address entity is loaded, it can happen, that it doesnt have the address extension
     * and the relevant data from address check in the entity (but has it in the database), because
     * those data have been added in the same request process. So we use this method to update the data inside the
     * entity in order to have correct display on the frontend without having to reload the page.
     *
     * @param OrderAddressEntity $addressEntity
     * @return void
     */
    public function syncOrderAddressEntity(OrderAddressEntity $addressEntity): void;
}