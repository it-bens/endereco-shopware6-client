<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\Check;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

interface IsStreetSplitRequiredCheckerInterface
{
    /**
     * Determines whether a street splitting operation is necessary for the given address.
     *
     * This method accepts an AddressEntity and a corresponding EnderecoAddressExtension.
     * It constructs an expected full street string using the street and house number from the EnderecoAddressExtension,
     * along with the country ISO code from the AddressEntity.
     *
     * The expected full street string is then compared to the current full street string stored
     * in the AddressEntity. The country code is fetched by the `getCountryCodeById` method
     * and 'DE' is used as a default if the country code cannot be determined.
     *
     * If the expected and current full street strings do not match, the method returns true,
     * indicating that a street splitting operation is necessary. If they do match, the method
     * returns false, indicating that no street splitting operation is required.
     */
    public function checkIfStreetSplitIsRequired(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        Context $context
    ): bool;
}
