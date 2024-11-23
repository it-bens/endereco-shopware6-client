<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\OrderAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\OrderAddress\OrderAddressExtension;
use Endereco\Shopware6Client\Service\AddressCheck\CountryCodeFetcherInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\StreetIsSplitBaseInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsStreetSplitRequiredCheckerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class StreetIsSplitInsurance extends StreetIsSplitBaseInsurance implements IntegrityInsurance
{
    private EntityRepository $addressExtensionRepository;

    public function __construct(
        IsStreetSplitRequiredCheckerInterface $isStreetSplitRequiredChecker,
        CountryCodeFetcherInterface $countryCodeFetcher,
        EnderecoService $enderecoService,
        EntityRepository $addressExtensionRepository
    ) {
        $this->addressExtensionRepository = $addressExtensionRepository;

        parent::__construct($isStreetSplitRequiredChecker, $countryCodeFetcher, $enderecoService);
    }

    public static function getPriority(): int
    {
        return -10;
    }

    /**
     * Ensures that the full street address of a given address entity is properly split into street name and building
     * number.
     *
     * This method accepts a OrderAddressEntity and a Context. It retrieves the corresponding
     * EnderecoAddressExtensionEntity for the address and the full street address stored in the OrderAddressEntity.
     * It checks whether a street splitting operation is needed by comparing the expected full street (constructed using
     * data from the EnderecoAddressExtensionEntity) with the current full street.
     *
     * If the street address is not empty and street splitting is needed, the method splits the full street address into
     * street name and building number using the 'splitStreet' method of the Endereco service. The country code for
     * splitting the street is retrieved using the 'getCountryCodeById' method (defaulting to 'DE' if unknown). The
     * split street name and building number are then saved back into the EnderecoAddressExtensionEntity for the
     * address.
     */
    public function ensure(OrderAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(OrderAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoOrderAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure($addressEntity, $addressExtension, $salesChannelId, $context);
    }

    protected function getAddressExtensionRepository(): EntityRepository
    {
        return $this->addressExtensionRepository;
    }
}
