<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a core config item')
            ->addArgument('path', InputArgument::OPTIONAL, 'The config path')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            if(!$input->getArgument('path') || ($wildcard = strpos($input->getArgument('path'), '*')) !== false) {
                $collection = \Mage::getModel('core/config_data')->getCollection();
                if($wildcard) {
                    $collection->addFieldToFilter('path', array(
                        'like' => str_replace('*', '%', $input->getArgument('path'))
                    ));
                }
                foreach ($collection as $item){
                    $table[$item->getPath()] = array(
                        'path' => $item->getPath(),
                        'value' => substr($item->getValue(), 0, 50)
                    );
                }
                ksort($table);
                $this->getHelper('table')->write($output, $table);
            } else {
                
                $value = \Mage::getStoreConfig($input->getArgument('path'));
                $output->writeln($input->getArgument('path') . " => " . $value);
            }
        }
    }
}