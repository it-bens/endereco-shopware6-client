<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressCorrection;

use Endereco\Shopware6Client\Model\AddressCorrectionScope;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class AddressCorrectionScopeBuilder implements AddressCorrectionScopeBuilderInterface
{
    private EnderecoService $enderecoService;
    private SystemConfigService $systemConfigService;

    public function __construct(
        EnderecoService $enderecoService,
        SystemConfigService $systemConfigService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->enderecoService = $enderecoService;
    }

    public function buildAddressCorrectionScope(
        bool $isPayPalAddress,
        bool $isAmazonPayAddress,
        Context $context
    ): AddressCorrectionScope {
        $salesChannelId = $this->enderecoService->fetchSalesChannelId($context);

        $allowNativeAddressFieldsOverwrite = $this->systemConfigService->getBool(
            'EnderecoShopware6Client.config.enderecoAllowNativeAddressFieldsOverwrite',
            $salesChannelId
        );

        return new AddressCorrectionScope($allowNativeAddressFieldsOverwrite, $isPayPalAddress, $isAmazonPayAddress);
    }
}
