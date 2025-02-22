<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressCorrection;

use Endereco\Shopware6Client\Model\AddressCorrectionScope;
use Shopware\Core\Framework\Context;

interface AddressCorrectionScopeBuilderInterface
{
    public function buildAddressCorrectionScope(
        bool $isPayPalAddress,
        bool $isAmazonPayAddress,
        Context $context
    ): AddressCorrectionScope;
}
