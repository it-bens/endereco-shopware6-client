<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance;

use Endereco\Shopware6Client\Model\AddressPersistenceStrategy;
use Shopware\Core\Framework\Context;

interface AddressPersistenceStrategyProviderInterface
{
    public function getStrategy(
        bool $isPayPalAddress,
        bool $isAmazonPayAddress,
        Context $context
    ): AddressPersistenceStrategy;
}
