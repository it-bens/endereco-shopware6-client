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
     * @param array<string|int, mixed> $data
     */
    public function compare(array $data): bool
    {
        $billingAddressValidationData = $data[self::BILLING_ADDRESS_VALIDATION_DATA] ?? [];
        $thisBillingAddressValidationData = $this->billingAddressValidationData ?? [];
        if (count($this->doCompare($billingAddressValidationData, $thisBillingAddressValidationData)) > 0) {
            return false;
        }

        $shippingAddressValidationData = $data[self::SHIPPING_ADDRESS_VALIDATION_DATA] ?? [];
        $thisShippingAddressValidationData = $this->shippingAddressValidationData ?? [];
        if (count($this->doCompare($shippingAddressValidationData, $thisShippingAddressValidationData)) > 0) {
            return false;
        }

        return true;
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

    /**
     * @param array<int|string, mixed> $data1
     * @param mixed $data2
     * @return array<int|string, mixed>
     */
    private function doCompare(array $data1, mixed $data2): array
    {
        if (!is_array($data2)) {
            return $data1;
        }

        $result = array();

        foreach ($data1 as $key => $value) {
            if (!array_key_exists($key, $data2)) {
                $result[$key] = $value;
                continue;
            }

            if (is_array($value) && count($value) > 0) {
                $recursiveArrayDiff = $this->doCompare($value, $data2[$key]);

                if (count($recursiveArrayDiff) > 0) {
                    $result[$key] = $recursiveArrayDiff;
                }

                continue;
            }

            $value1 = $value;
            $value2 = $data2[$key];

            if (is_float($value1) && is_float($value2)) {
                $value1 = (string) $value1;
                $value2 = (string) $value2;
            }

            if ($value1 !== $value2) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
