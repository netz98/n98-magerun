<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlushCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:flush')
            ->setDescription('Flush magento cache storage')
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

        $this->banUseCache();

        if ($this->initMagento()) {

            \Mage::app()->loadAreaPart('adminhtml', 'events');
            \Mage::dispatchEvent('adminhtml_cache_flush_all', array('output' => $output));
            $result = \Mage::app()->getCacheInstance()->flush();
            if ($result) {
                $output->writeln('<info>Cache cleared</info>');
            } else {
                $output->writeln('<error>Failed to clear Cache</error>');
            }

            $this->reinitCache();

            /* Since Magento 1.10 we have an own cache handler for FPC */
            if ($this->isEnterpriseFullPageCachePresent()) {
                $result = \Enterprise_PageCache_Model_Cache::getCacheInstance()->flush();
                if ($result) {
                    $output->writeln('<info>FPC cleared</info>');
                } else {
                    $output->writeln('<error>Failed to clear FPC</error>');
                }
            }

        }
    }

    protected function isEnterpriseFullPageCachePresent()
    {

        $isModuleEnabled = \Mage::helper('core')->isModuleEnabled('Enterprise_PageCache');
        return $this->_magentoEnterprise && $isModuleEnabled && version_compare(\Mage::getVersion(), '1.11.0.0', '>=');
    }
}
