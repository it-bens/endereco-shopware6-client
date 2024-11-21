<?php

namespace Endereco\Shopware6Client\Service;

use Endereco\Shopware6Client\Model\ExpectedSystemConfigValue;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

interface BySystemConfigFilterInterface
{
    /**
     * @param EntityRepository $entityRepository
     * @param string $salesChannelIdField
     * @param string[] $entityIds
     * @param ExpectedSystemConfigValue[] $expectedSystemConfigValues
     * @param Context $context
     * @return string[]
     */
    public function filterEntityIdsBySystemConfig(
        EntityRepository $entityRepository,
        string $salesChannelIdField,
        array $entityIds,
        array $expectedSystemConfigValues,
        Context $context
    ): array;
}
