<?php

namespace Endereco\Shopware6Client\Service\AddressCheck;

use Shopware\Core\Framework\Context;

interface LocaleFetcherInterface
{
    /**
     * Fetches the locale from the sales channel domain associated with a given sales channel ID.
     *
     * The final returned string is a 2-character locale code.
     *
     * @param Context $context The context which includes details of the event triggering this method.
     * @param string $salesChannelId The ID of the sales channel whose locale is to be fetched.
     *
     * @return string The 2-character locale code associated with the sales channel.
     *
     * @throws \RuntimeException If the sales channel with the provided ID cannot be found.
     */
    public function fetchLocaleBySalesChannelId(string $salesChannelId, Context $context): string;
}