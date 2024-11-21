<?php

namespace Endereco\Shopware6Client\Model;

class ExpectedSystemConfigValue
{
    private string $configKey;
    /** @var bool|string|int|float $expectedConfigValue */
    private $expectedConfigValue;

    /**
     * @param string $configKey
     * @param bool|string|int|float $expectedConfigValue
     */
    public function __construct(string $configKey, $expectedConfigValue)
    {
        $this->configKey = $configKey;
        $this->expectedConfigValue = $expectedConfigValue;
    }

    public function getFullyQualifiedConfigKey(): string
    {
        return 'EnderecoShopware6Client.config.' . $this->configKey;
    }

    /**
     * @return bool|float|int|string
     */
    public function getExpectedConfigValue()
    {
        return $this->expectedConfigValue;
    }
}
