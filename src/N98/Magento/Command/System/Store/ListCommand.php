<?php

namespace N98\Magento\Command\System\Store;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:store:list')
            ->setDescription('Lists all installed store-views')
            ->addFormatOption()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = [];
        $this->detectMagento($output, true);
        $this->initMagento();

        foreach (Mage::app()->getStores() as $store) {
            $table[$store->getId()] = [$store->getId(), $store->getCode()];
        }

        ksort($table);
        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(['id', 'code'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
