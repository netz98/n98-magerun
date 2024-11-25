<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ListCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:list')
            ->setDescription('Lists all magento caches')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
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

        return Command::SUCCESS;
    }
}
