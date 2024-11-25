<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Store\Config;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List stores base url command
 *
 * @package N98\Magento\Command\System\Store\Config
 */
class BaseUrlListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:store:config:base-url:list')
            ->setDescription('Lists all base urls')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];
        $this->detectMagento($output);

        if (!$input->getOption('format')) {
            $this->writeSection($output, 'Magento Stores - Base URLs');
        }

        $this->initMagento();

        foreach (Mage::app()->getStores() as $store) {
            $table[$store->getId()] = [
                $store->getId(),
                $store->getCode(),
                Mage::getStoreConfig('web/unsecure/base_url', $store),
                Mage::getStoreConfig('web/secure/base_url', $store),
            ];
        }

        ksort($table);

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['id', 'code', 'unsecure_baseurl', 'secure_baseurl'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
