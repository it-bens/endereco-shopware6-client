<?php

namespace Endereco\Shopware6Client\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class BySystemConfigFilter implements BySystemConfigFilterInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function filterEntityIdsBySystemConfig(
        EntityRepository $entityRepository,
        string $salesChannelIdField,
        array $entityIds,
        array $expectedSystemConfigValues,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $entityIds));
        $criteria->addAggregation(new TermsAggregation('sales-channel-ids', $salesChannelIdField));
        $criteria->addFields(['id']);
        $salesChannelIdsAggregation = $entityRepository
            ->search($criteria, $context)
            ->getAggregations()
            ->get('sales-channel-ids');

        if (!$salesChannelIdsAggregation instanceof TermsResult) {
            return [];
        }

        $allowedSalesChannels = [];
        foreach ($salesChannelIdsAggregation->getKeys() as $salesChannelId) {
            $allowed = true;
            foreach ($expectedSystemConfigValues as $expectedSystemConfigValue) {
                $systemConfigValue = $this->systemConfigService->get(
                    $expectedSystemConfigValue->getFullyQualifiedConfigKey(),
                    $salesChannelId
                );
                if ($expectedSystemConfigValue->getExpectedConfigValue() !== $systemConfigValue) {
                    $allowed = false;
                    break;
                }
            }

            if ($allowed === true) {
                $allowedSalesChannels[] = $salesChannelId;
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $entityIds));
        $criteria->addFilter(new EqualsAnyFilter($salesChannelIdField, $allowedSalesChannels));

        $filteredEntityIds = $entityRepository->searchIds($criteria, $context)->getIds();

        return $this->flattenIds($filteredEntityIds);
    }

    /**
     * @param list<string>|list<array<string, string>> $entityIds
     * @return string[]
     */
    private function flattenIds(array $entityIds): array
    {
        $flattenedIds = [];
        foreach ($entityIds as $entityId) {
            if (is_array($entityId)) {
                throw new \RuntimeException(
                    'Only 1D arrays are supported for now. If this exception is thrown, contact the author.'
                );
            }

            $flattenedIds[] = $entityId;
        }

        return $flattenedIds;
    }
}
