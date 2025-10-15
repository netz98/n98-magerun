<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Carbon\Carbon;
use Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ReportCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:report')
            ->setDescription('View inside the cache')
            ->addOption('tags', 't', InputOption::VALUE_NONE, 'Output tags')
            ->addOption('mtime', 'm', InputOption::VALUE_NONE, 'Output last modification time')
            ->addOption('filter-id', '', InputOption::VALUE_OPTIONAL, 'Filter output by ID (substring)')
            ->addOption(
                'filter-tag',
                '',
                InputOption::VALUE_OPTIONAL,
                'Filter output by TAG (separate multiple tags by comma)',
            )
            ->addFormatOption()
        ;
    }

    /**
     * @param array<string, mixed> $metaData
     */
    protected function isTagFiltered(array $metaData, InputInterface $input): bool
    {
        return (bool) count(array_intersect($metaData['tags'], explode(',', $input->getOption('filter-tag'))));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $cacheInstance = Mage::app()->getCache();
        $cacheIds = $cacheInstance->getIds();
        $table = [];
        foreach ($cacheIds as $cacheId) {
            if ($input->getOption('filter-id') !== null && (in_array(stristr($cacheId, (string) $input->getOption('filter-id')), ['', '0'], true) || stristr($cacheId, (string) $input->getOption('filter-id')) === false)) {
                continue;
            }

            $metaData = $cacheInstance->getMetadatas($cacheId);
            if ($input->getOption('filter-tag') !== null && !$this->isTagFiltered($metaData, $input)) {
                continue;
            }

            $row = [$cacheId, Carbon::createFromTimestamp($metaData['expire'])->format('Y-m-d H:i:s')];
            if ($input->getOption('mtime')) {
                $row[] = Carbon::createFromTimestamp($metaData['mtime'])->format('Y-m-d H:i:s');
            }

            if ($input->getOption('tags')) {
                $row[] = implode(',', $metaData['tags']);
            }

            $table[] = $row;
        }

        $headers = ['ID', 'EXPIRE'];
        if ($input->getOption('mtime')) {
            $headers[] = 'MTIME';
        }

        if ($input->getOption('tags')) {
            $headers[] = 'TAGS';
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders($headers)
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
