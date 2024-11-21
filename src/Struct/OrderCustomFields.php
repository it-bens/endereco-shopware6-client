<?php

namespace Endereco\Shopware6Client\Struct;

final class OrderCustomFields
{
    public const BILLING_ADDRESS_VALIDATION_DATA = 'endereco_order_billing_addresses_validation_data';
    public const SHIPPING_ADDRESS_VALIDATION_DATA = 'endereco_order_shipping_addresses_validation_data';
    public const FIELDS = [
        self::BILLING_ADDRESS_VALIDATION_DATA,
        self::SHIPPING_ADDRESS_VALIDATION_DATA,
    ];

    /** @var array<string, array<string, mixed>> */
    private array $billingAddressValidationData;
    /** @var array<string, array<string, mixed>> */
    private array $shippingAddressValidationData;

    /**
     * @param array<string, array<string, mixed>> $billingAddressValidationData
     * @param array<string, array<string, mixed>> $shippingAddressValidationData
     */
    public function __construct(
        array $billingAddressValidationData,
        array $shippingAddressValidationData
    ) {
        $this->billingAddressValidationData = $billingAddressValidationData;
        $this->shippingAddressValidationData = $shippingAddressValidationData;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function data(): array
    {
        return [
            self::BILLING_ADDRESS_VALIDATION_DATA => $this->billingAddressValidationData,
            self::SHIPPING_ADDRESS_VALIDATION_DATA => $this->shippingAddressValidationData,
        ];
    }
}
