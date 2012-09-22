<?php

namespace N98\Magento\Command\System\Store;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setName('system:store:list')
            ->setAliases(array('sys:store:list'))
            ->setDescription('Lists all installed store-views');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Magento Stores');
        $this->initMagento();

        foreach (\Mage::app()->getStores() as $store) {
            $table[$store->getId()] = array(
                'id'   => '  ' . $store->getId(),
                'code' => $store->getCode(),
            );
        }

        ksort($table);
        $this->getHelper('table')->write($output, $table);
    }
}