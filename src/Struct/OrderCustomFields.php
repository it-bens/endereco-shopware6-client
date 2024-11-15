<?php

namespace Endereco\Shopware6Client\Struct;

final class OrderCustomFields
{
    public const ADDRESS_VALIDATION_DATA = 'endereco_order_addresses_validation_data';

    /** @var array<string, array<string, mixed>> */
    private array $addressValidationData;

    /**
     * @param array<string, array<string, mixed>> $addressValidationData
     */
    public function __construct(
        array $addressValidationData
    ) {
        $this->addressValidationData = $addressValidationData;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function data(): array
    {
        return [
            self::ADDRESS_VALIDATION_DATA => $this->addressValidationData
        ];
    }
}
