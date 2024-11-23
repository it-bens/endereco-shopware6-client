<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\FlagIsSetInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress\IntegrityInsurance;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;

final class AmazonFlagIsSetInsurance extends FlagIsSetInsurance implements IntegrityInsurance
{
    public static function getPriority(): int
    {
        return -10;
    }

    /**
     * Ensures that the 'isAmazonPayAddress' flag is set in the 'EnderecoAddressExtension' of a customer's address.
     *
     * The function first retrieves the customer based on the provided CustomerAddressEntity. Then, it checks if
     * 'swag_amazon_pay_account_id' exists in the customer's custom fields. If so, 'isAmazonPayAddress' is set to true.
     *
     * The function then gets or creates the 'EnderecoAddressExtension' for the provided address,
     * and sets the 'isAmazonPayAddress' value in it.
     *
     * @param CustomerAddressEntity $addressEntity The customer's address entity.
     * @param string $salesChannelId The ID of the sales channel the address is associated with.
     * @param Context $context The context for the search and upsert operations.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function ensure(CustomerAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure(
            $addressEntity,
            $addressExtension,
            'swag_amazon_pay_account_id',
            'isAmazonPayAddress',
            $context
        );
    }

    protected function setFlag(EnderecoCustomerAddressExtensionEntity $addressExtension, bool $value): void
    {
        $addressExtension->setIsAmazonPayAddress($value);
    }
}
