<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;

final class LocaleFetcher implements LocaleFetcherInterface
{
    private EntityRepository $salesChannelDomainRepository;

    public function __construct(
        EntityRepository $salesChannelDomainRepository
    ) {
        $this->salesChannelDomainRepository = $salesChannelDomainRepository;
    }

    /**
     * This implementation constructs a new criteria object and adds a filter to match the provided sales channel ID.
     * It then uses this criteria to search the sales channel domain repository. The first matching
     * sales channel domain is retrieved, and the locale of its language is fetched.
     */
    public function fetchLocaleBySalesChannelId(string $salesChannelId, Context $context): string
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('salesChannelId', $salesChannelId))
            ->addAssociation('language.locale');

        if (!empty($context->getLanguageId())) {
            $criteria->addFilter(new EqualsFilter('languageId', $context->getLanguageId()));
        }

        /** @var SalesChannelDomainEntity|null $salesChannelDomain */
        $salesChannelDomain = $this->salesChannelDomainRepository->search($criteria, $context)->first();

        if (!$salesChannelDomain) {
            throw new \RuntimeException(sprintf('Sales channel with id %s not found', $salesChannelId));
        }

        // Get the locale code from the sales channel
        $language = $salesChannelDomain->getLanguage();
        if ($language === null) {
            throw new \RuntimeException('Language entity is not available.');
        }

        $locale = $language->getLocale();
        if ($locale === null) {
            throw new \RuntimeException('Locale entity is not available.');
        }

        return substr($locale->getCode(), 0, 2);
    }
}
