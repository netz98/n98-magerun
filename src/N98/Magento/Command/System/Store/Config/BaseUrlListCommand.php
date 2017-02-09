<?php

namespace N98\Magento\Command\System\Store\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseUrlListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:store:config:base-url:list')
            ->setDescription('Lists all base urls')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if (!$input->getOption('format')) {
            $this->writeSection($output, 'Magento Stores - Base URLs');
        }
        $this->initMagento();

        foreach (\Mage::app()->getStores() as $store) {
            $table[$store->getId()] = array(
                $store->getId(),
                $store->getCode(),
                \Mage::getStoreConfig('web/unsecure/base_url', $store),
                \Mage::getStoreConfig('web/secure/base_url', $store),
            );
        }

        ksort($table);
        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('id', 'code', 'unsecure_baseurl', 'secure_baseurl'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
