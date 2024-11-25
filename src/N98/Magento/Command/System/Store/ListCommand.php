<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List stores command
 *
 * @package N98\Magento\Command\System\Store
 */
class ListCommand extends AbstractMagentoCommand
{
    protected array $infos;

    protected function configure(): void
    {
        $this
            ->setName('sys:store:list')
            ->setDescription('Lists all installed store-views')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];
        $this->detectMagento($output);
        $this->initMagento();

        foreach (Mage::app()->getStores() as $store) {
            $table[$store->getId()] = [$store->getId(), $store->getCode()];
        }

        ksort($table);

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['id', 'code'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
