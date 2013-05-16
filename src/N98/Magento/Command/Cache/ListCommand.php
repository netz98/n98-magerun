<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:list')
            ->setDescription('Lists all magento caches')
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
        $this->writeSection($output, 'Cache list');
        if ($this->initMagento($output)) {
            $cacheTypes = $this->_getCacheModel()->getTypes();
            $table = array();
            foreach ($cacheTypes as $cacheCode => $cacheInfo) {
                $table[] = array(
                    'code'   => $cacheCode,
                    'status' => $cacheInfo['status'] ? 'enabled' : 'disabled'
                );
            }

            $this->getHelper('table')->write($output, $table);
        }
    }
}