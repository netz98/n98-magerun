<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ListCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:list')
            ->setDescription('Lists all magento caches')
            ->addFormatOption()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $cacheTypes = $this->_getCacheModel()->getTypes();
        $table = [];
        foreach ($cacheTypes as $cacheCode => $cacheInfo) {
            $table[] = [$cacheCode, $cacheInfo['status'] ? 'enabled' : 'disabled'];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['code', 'status'])
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }
}
