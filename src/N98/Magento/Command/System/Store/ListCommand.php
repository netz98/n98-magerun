<?php

namespace N98\Magento\Command\System\Store;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

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
        $this->initMagento();

        foreach (\Mage::app()->getStores() as $store) {
            $table[$store->getId()] = array(
                $store->getId(),
                $store->getCode(),
            );
        }

        ksort($table);
        $this->getHelper('table')
            ->setHeaders(array('id', 'code'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
