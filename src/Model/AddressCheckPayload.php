<?php

namespace Endereco\Shopware6Client\Model;

class AddressCheckPayload
{
    private string $language;
    private string $country;
    private string $postCode;
    private string $cityName;
    private string $streetFull;
    private ?string $subdivisionCode;

    /**
     * $subdivisionCode = null: no country state was chosen and the country state has none
     * $subdivisionCode = '': no country state was chosen but the country state has one
     * $subdivisionCode = string: a country state was chosen
     *
     * @param string $language
     * @param string $country
     * @param string $postCode
     * @param string $cityName
     * @param string $streetFull
     * @param string|null $subdivisionCode
     */
    public function __construct(
        string $language,
        string $country,
        string $postCode,
        string $cityName,
        string $streetFull,
        ?string $subdivisionCode
    ) {
        $this->language = $language;
        $this->country = $country;
        $this->postCode = $postCode;
        $this->cityName = $cityName;
        $this->streetFull = $streetFull;
        $this->subdivisionCode = $subdivisionCode;
    }

    /**
     * The subdivisionCode is only added to the data if it is not null.
     *
     * @return array{
     *     language: string,
     *     country: string,
     *     postCode: string,
     *     cityName: string,
     *     streetFull: string,
     *     subdivisionCode?: string
     * }
     */
    public function data(): array
    {
        $data = [
            'language' => $this->language,
            'country' => $this->country,
            'postCode' => $this->postCode,
            'cityName' => $this->cityName,
            'streetFull' => $this->streetFull,
        ];

        if ($this->subdivisionCode !== null) {
            $data['subdivisionCode'] = $this->subdivisionCode;
        }

        return $data;
    }
}
