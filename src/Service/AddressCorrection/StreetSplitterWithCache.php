<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Service\AddressCorrection;

use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class StreetSplitterWithCache implements StreetSplitterInterface
{
    public const CACHE_TAG = 'street_splitting';

    private const CACHE_KEY_TEMPLATE = 'street_splitting.%s';

    private const CACHE_TTL = 3600;

    private TagAwareCacheInterface $cache;

    private StreetSplitterInterface $streetSplitter;

    public function __construct(TagAwareCacheInterface $cache, StreetSplitterInterface $streetSplitter)
    {
        $this->cache = $cache;
        $this->streetSplitter = $streetSplitter;
    }

    public function splitStreet(
        string $fullStreet,
        ?string $additionalInfo,
        string $countryCode,
        Context $context,
        ?string $salesChannelId
    ): array {
        $dataHash = $this->generateDataHash($fullStreet, $additionalInfo, $countryCode);
        $cacheKey = sprintf(self::CACHE_KEY_TEMPLATE, $dataHash);

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use (
                $fullStreet,
                $additionalInfo,
                $countryCode,
                $context,
                $salesChannelId
            ): array {
                $item->tag(self::CACHE_TAG);
                $item->expiresAfter(self::CACHE_TTL);

                return $this->streetSplitter->splitStreet(
                    $fullStreet,
                    $additionalInfo,
                    $countryCode,
                    $context,
                    $salesChannelId
                );
            }
        );
    }

    private function generateDataHash(string $fullStreet, ?string $additionalInfo, string $countryCode): string
    {
        return md5($fullStreet . $additionalInfo . $countryCode);
    }
}
