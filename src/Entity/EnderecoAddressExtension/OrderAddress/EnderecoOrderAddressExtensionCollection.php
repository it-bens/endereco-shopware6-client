<?php

namespace Endereco\Shopware6Client\Entity\EnderecoAddressExtension\OrderAddress;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EnderecoOrderAddressExtensionEntity>
 */
class EnderecoOrderAddressExtensionCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'endereco_order_address_extension_collection';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildDataForOrderCustomField(): array
    {
        return $this->fmap(static function (EnderecoOrderAddressExtensionEntity $orderAddressExtensionEntity) {
            return $orderAddressExtensionEntity->buildDataForOrderCustomField();
        });
    }

    protected function getExpectedClass(): string
    {
        return EnderecoOrderAddressExtensionEntity::class;
    }
}
