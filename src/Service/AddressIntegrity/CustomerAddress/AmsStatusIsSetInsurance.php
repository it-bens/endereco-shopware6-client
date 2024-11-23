<?php

namespace Endereco\Shopware6Client\Service\AddressIntegrity\CustomerAddress;

use Endereco\Shopware6Client\Entity\CustomerAddress\CustomerAddressExtension;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress\EnderecoOrderAddressExtensionEntity;
use Endereco\Shopware6Client\Model\FailedAddressCheckResult;
use Endereco\Shopware6Client\Service\AddressCacheInterface;
use Endereco\Shopware6Client\Service\AddressIntegrity\Address\AmsStatusIsSetBaseInsurance;
use Endereco\Shopware6Client\Service\AddressIntegrity\Check\IsAmsRequestPayloadIsUpToDateCheckerInterface;
use Endereco\Shopware6Client\Service\EnderecoService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;

final class AmsStatusIsSetInsurance extends AmsStatusIsSetBaseInsurance implements IntegrityInsurance
{
    private EnderecoService $enderecoService;
    private AddressCacheInterface $addressEntityCache;

    public function __construct(
        IsAmsRequestPayloadIsUpToDateCheckerInterface $isAmsRequestPayloadIsUpToDateChecker,
        EnderecoService $enderecoService,
        AddressCacheInterface $addressEntityCache
    ) {
        parent::__construct($isAmsRequestPayloadIsUpToDateChecker);

        $this->enderecoService = $enderecoService;
        $this->addressEntityCache = $addressEntityCache;
    }

    public static function getPriority(): int
    {
        return -20;
    }

    /**
     * Ensures that an address status is set by checking and applying the result from the Endereco API.
     *
     * This method checks whether a new status is needed for the address. If so, it uses the Endereco service
     * to check the address. If the check fails, it doesn't throw an exception but simply stops.
     *
     * If a session ID was used in the check, it adds it to the accountable session IDs storage.
     * Then, it applies the check result to the address entity.
     */
    public function ensure(CustomerAddressEntity $addressEntity, string $salesChannelId, Context $context): void
    {
        $addressExtension = $addressEntity->getExtension(CustomerAddressExtension::ENDERECO_EXTENSION);
        if (!$addressExtension instanceof EnderecoCustomerAddressExtensionEntity) {
            throw new \RuntimeException('The address extension should be set at this point');
        }

        $this->doEnsure($addressEntity, $addressExtension, $salesChannelId, $context);
    }

    protected function isValidationRequired(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        string $salesChannelId,
    ): bool {
        $this->checkAddressEntityType($addressEntity);
        /** @var CustomerAddressEntity $addressEntity */

        // Determine if existing customer address check or PayPal checkout address check is required
        $existingCustomerCheckIsRelevant =
            $this->enderecoService->isExistingAddressCheckFeatureEnabled($salesChannelId)
            && !$this->enderecoService->isAddressFromRemote($addressEntity)
            && !$this->enderecoService->isAddressRecent($addressEntity);

        $paypalExpressCheckoutCheckIsRelevant =
            $this->enderecoService->isPayPalCheckoutAddressCheckFeatureEnabled($salesChannelId)
            && $this->enderecoService->isAddressFromPayPal($addressEntity);

        if ($existingCustomerCheckIsRelevant === false && $paypalExpressCheckoutCheckIsRelevant === false) {
            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function reValidateAddress(
        CustomerAddressEntity|OrderAddressEntity $addressEntity,
        EnderecoCustomerAddressExtensionEntity|EnderecoOrderAddressExtensionEntity $addressExtension,
        string $salesChannelId,
        Context $context
    ): void {
        $this->checkAddressEntityType($addressEntity);
        /** @var CustomerAddressEntity $addressEntity */

        $addressCheckResult = $this->enderecoService->checkAddress($addressEntity, $context, $salesChannelId);

        // We dont throw exceptions, we just gracefully stop here. Maybe the API will be available later again.
        if ($addressCheckResult instanceof FailedAddressCheckResult) {
            return;
        }

        if (!empty($addressCheckResult->getUsedSessionId())) {
            $this->enderecoService->addAccountableSessionIdsToStorage([$addressCheckResult->getUsedSessionId()]);
        }

        // Here we save the status codes and predictions. If it's an automatic correction, then we also save
        // the data from the correction to customer address entity and generate a new,
        // "virtual" address check result.
        $this->enderecoService->applyAddressCheckResult($addressCheckResult, $addressEntity, $context);

        // Cache the entity, in case others entities might need an update. We will just copy values from this one.
        $this->addressEntityCache->set($addressEntity);
    }

    private function checkAddressEntityType(CustomerAddressEntity|OrderAddressEntity $addressEntity): void
    {
        if (!$addressEntity instanceof CustomerAddressEntity) {
            throw new \InvalidArgumentException(
                sprintf('The address entity must be an instance of %s', CustomerAddressEntity::class)
            );
        }
    }
}
