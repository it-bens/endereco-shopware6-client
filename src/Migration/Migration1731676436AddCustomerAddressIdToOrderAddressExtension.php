<?php

declare(strict_types=1);

namespace Endereco\Shopware6Client\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1731676436AddCustomerAddressIdToOrderAddressExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1731676436;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `endereco_order_address_ext`
            ADD COLUMN `original_customer_address_id` BINARY(16) NULL DEFAULT NULL AFTER `address_id`,
            ADD CONSTRAINT `fk.end_order_address_original_customer_address_id`
                FOREIGN KEY (`original_customer_address_id`) REFERENCES `customer_address` (`id`) 
                    ON UPDATE CASCADE 
                    ON DELETE SET NULL;
        SQL;
        $connection->executeStatement($sql);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
