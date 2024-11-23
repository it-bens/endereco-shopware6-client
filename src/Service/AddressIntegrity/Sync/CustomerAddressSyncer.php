<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Sync;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

final class CustomerAddressSyncer implements CustomerAddressSyncerInterface
{
    private AddressCacheInterface $addressCache;

    public function __construct(AddressCacheInterface $addressCache)
    {
        $this->addressCache = $addressCache;
    }

    public function syncCustomerAddressEntity(CustomerAddressEntity $addressEntity): void
    {
        $cachedAddressEntity = $this->addressCache->get($addressEntity->getId());
        if ($cachedAddressEntity === null) {
            return;
        }

        $cachedAddressExtension = $cachedAddressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        if (!$cachedAddressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            throw new \Exception('Cached address entities should always have the extension, but this one has not.');
        }

        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);

        if (!$addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            $addressExtension = new EnderecoCustomerAddressExtensionEntity();
            $addressEntity->addExtension(CustomerAddressExtension::ENDERECO_EXTENSION, $addressExtension);
        }

        $addressEntity->setZipcode($cachedAddressEntity->getZipcode());
        $addressEntity->setCity($cachedAddressEntity->getCity());
        $addressEntity->setStreet($cachedAddressEntity->getStreet());
        if ($cachedAddressEntity->getCountryStateId() !== null) {
            $addressEntity->setCountryStateId($cachedAddressEntity->getCountryStateId());
        }

        $addressExtension->sync($addressExtension);
    }
}
