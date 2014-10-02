<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
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

    public function isEnabled()
    {
        return $this->getApplication()->getMagentoMajorVersion() == Application::MAGENTO_MAJOR_VERSION_1;
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

            \Mage::app()->loadAreaPart('adminhtml', 'events');
            \Mage::dispatchEvent('adminhtml_cache_flush_all', array('output' => $output));
            \Mage::app()->getCacheInstance()->flush();
            $output->writeln('<info>Cache cleared</info>');

            /* Since Magento 1.10 we have an own cache handler for FPC */
            if ($this->_magentoEnterprise && \Mage::helper('core')->isModuleEnabled('Enterprise_PageCache') && version_compare(\Mage::getVersion(), '1.11.0.0', '>=')) {
                \Enterprise_PageCache_Model_Cache::getCacheInstance()->flush();
                $output->writeln('<info>FPC cleared</info>');
            }

        }
    }
}