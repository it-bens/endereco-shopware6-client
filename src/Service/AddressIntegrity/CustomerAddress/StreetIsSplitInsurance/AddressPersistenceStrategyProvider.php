<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\StreetIsSplitInsurance;

use Endereco\Shopware6Client\Model\AddressPersistenceStrategy;
use Endereco\Shopware6Client\Service\AddressCheck\AdditionalAddressFieldCheckerInterface;
use Endereco\Shopware6Client\Service\AddressCorrection\AddressCorrectionScopeBuilderInterface;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\WithAllowedNativeAddressOverrideIncludingAdditionalAddressLine1;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\WithAllowedNativeAddressOverrideIncludingAdditionalAddressLine2;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\WithAllowedNativeAddressOverrideNotIncludeAdditionalAddressLine;
use Endereco\Shopware6Client\Model\AddressPersistenceStrategy\WithForbiddenNativeAddressOverride;
use Shopware\Core\Framework\Context;

final class AddressPersistenceStrategyProvider implements AddressPersistenceStrategyProviderInterface
{
    private AddressCorrectionScopeBuilderInterface $addressCorrectionScopeBuilder;
    private AdditionalAddressFieldCheckerInterface $additionalAddressFieldChecker;

    public function __construct(
        AddressCorrectionScopeBuilderInterface $addressCorrectionScopeBuilder,
        AdditionalAddressFieldCheckerInterface $additionalAddressFieldChecker
    ) {
        $this->additionalAddressFieldChecker = $additionalAddressFieldChecker;
        $this->addressCorrectionScopeBuilder = $addressCorrectionScopeBuilder;
    }

    public function getStrategy(
        bool $isPayPalAddress,
        bool $isAmazonPayAddress,
        Context $context
    ): AddressPersistenceStrategy {
        $addressCorrectionScope = $this->addressCorrectionScopeBuilder->buildAddressCorrectionScope(
            $isPayPalAddress,
            $isAmazonPayAddress,
            $context
        );

        if ($addressCorrectionScope->isNativeAddressFieldsOverwriteAllowed() === false) {
            return new WithForbiddenNativeAddressOverride();
        }

        if ($this->additionalAddressFieldChecker->hasAdditionalAddressField($context) === false) {
            return new WithAllowedNativeAddressOverrideNotIncludeAdditionalAddressLine();
        }

        $availableAdditionalAddressFieldName = $this->additionalAddressFieldChecker
            ->getAvailableAdditionalAddressFieldName($context);
        if ($availableAdditionalAddressFieldName === '') {
            return new WithAllowedNativeAddressOverrideNotIncludeAdditionalAddressLine();
        }

        if ($availableAdditionalAddressFieldName === 'additionalAddressLine1') {
            return new WithAllowedNativeAddressOverrideIncludingAdditionalAddressLine1();
        }

        if ($availableAdditionalAddressFieldName === 'additionalAddressLine2') {
            return new WithAllowedNativeAddressOverrideIncludingAdditionalAddressLine2();
        }

        throw new \RuntimeException(
            sprintf(
                'Unknown additional address field name: %s',
                $availableAdditionalAddressFieldName
            )
        );
    }
}
