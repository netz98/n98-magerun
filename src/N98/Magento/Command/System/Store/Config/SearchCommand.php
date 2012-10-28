<?php

namespace N98\Magento\Command\System\Store\Config;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractMagentoStoreConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:store:config:search')
            ->setDescription('Searches a store config entry from core_config_data table')
            ->addArgument('search', InputArgument::REQUIRED, 'Config path')
            ->addArgument('store', InputArgument::OPTIONAL, 'Store code or ID');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $store = $this->_initStore($input, $output);
            $config = \Mage::getConfig();
            $fullPath = 'stores/' . $store->getCode();

            $results = $config->getXpath('//' . $input->getArgument('search'));
            if ($results) {
                foreach ($results as $node) {
                    $path = $this->getCompletePath($node);
                    if (count($path) == 5) {
                        $foundPath = implode('/', array_reverse(array_slice($path, 0, 3)));
                        var_dump($foundPath);
                    }
                }
            }
            //$output->writeln('<comment>' . $value . '</comment>');
        }
    }

    /**
     * @param $node \Mage_Core_Model_Config_Element
     * @param string $path
     */
    protected function getCompletePath($node, $path = array())
    {
        $path[] = $node->getName();
        $parentNode = $node->getParent();
        if ($parentNode) {
            $path = $this->getCompletePath($parentNode, $path);
        }

        return $path;
    }
}
