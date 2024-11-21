<?php

namespace Endereco\Shopware6Client\Console;

use Endereco\Shopware6Client\Model\ExpectedSystemConfigValue;
use Endereco\Shopware6Client\Service\BySystemConfigFilterInterface;
use Endereco\Shopware6Client\Service\OrdersCustomFieldsUpdaterInterface;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(UpdateOrdersCustomFieldsCommand::COMMAND_NAME, UpdateOrdersCustomFieldsCommand::COMMAND_DESCRIPTION)]
class UpdateOrdersCustomFieldsCommand extends Command
{
    public const COMMAND_NAME = 'endereco:address-validation:order-custom-fields-update';
    public const COMMAND_DESCRIPTION =
        'Update the custom fields of orders based on the persisted order address validation data.';

    protected static string $defaultName = self::COMMAND_NAME;
    protected static string $defaultDescription = self::COMMAND_DESCRIPTION;

    private Context $context;
    private EntityRepository $orderRepository;
    private BySystemConfigFilterInterface $bySystemConfigFilter;
    private OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater;

    public function __construct(
        EntityRepository $orderRepository,
        BySystemConfigFilterInterface $bySystemConfigFilter,
        OrdersCustomFieldsUpdaterInterface $ordersCustomFieldsUpdater
    ) {
        // The method is internal since Shopware 6.6.1.0. After that, the `createCliContext` method can be used.
        $this->context = Context::createDefaultContext();

        $this->orderRepository = $orderRepository;
        $this->bySystemConfigFilter = $bySystemConfigFilter;
        $this->ordersCustomFieldsUpdater = $ordersCustomFieldsUpdater;

        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument(
            'order-ids',
            InputArgument::IS_ARRAY,
            'IDs of the orders for which the custom fields should be updated',
        );
        $this->setHelp('Providing no order IDs will lead to an update of all orders.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ShopwareStyle($input, $output);

        $orderIds = $input->getArgument('order-ids') ?? [];
        if (!is_array($orderIds)) {
            throw new \InvalidArgumentException('The Order ID list must be an array');
        }
        $orderIds = array_filter($orderIds);
        $orderIds = array_unique($orderIds);

        if (count($orderIds) === 0) {
            $io->info('No order IDs were provided. All orders will be updated.');
        }
        if (count($orderIds) > 0) {
            $io->info('The orders with IDs: ' . implode(', ', $orderIds) . ' will be updated.');
        }

        $orderIds = $this->bySystemConfigFilter->filterEntityIdsBySystemConfig(
            $this->orderRepository,
            'salesChannelId',
            $orderIds,
            [
                new ExpectedSystemConfigValue('enderecoActiveInThisChannel', true),
                new ExpectedSystemConfigValue('enderecoWriteOrderCustomFields', true)
            ],
            $this->context
        );

        $this->ordersCustomFieldsUpdater->updateOrdersCustomFields($orderIds, [], $this->context);

        return Command::SUCCESS;
    }
}
