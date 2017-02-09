<?php

namespace N98\Magento\Command\Cache;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCommand extends AbstractCacheCommand
{
    protected function configure()
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
                'Filter output by TAG (seperate multiple tags by comma)'
            )
            ->addOption(
                'fpc',
                null,
                InputOption::VALUE_NONE,
                'Use full page cache instead of core cache (Enterprise only!)'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    protected function isTagFiltered($metaData, $input)
    {
        return (bool) count(array_intersect($metaData['tags'], explode(',', $input->getOption('filter-tag'))));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws RuntimeException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        if ($input->hasOption('fpc') && $input->getOption('fpc')) {
            if (!class_exists('\Enterprise_PageCache_Model_Cache')) {
                throw new RuntimeException('Enterprise page cache not found');
            }
            $cacheInstance = \Enterprise_PageCache_Model_Cache::getCacheInstance()->getFrontend();
        } else {
            $cacheInstance = \Mage::app()->getCache();
        }
        /* @var $cacheInstance \Varien_Cache_Core */
        $cacheIds = $cacheInstance->getIds();
        $table = array();
        foreach ($cacheIds as $cacheId) {
            if ($input->getOption('filter-id') !== null && !stristr($cacheId, $input->getOption('filter-id'))) {
                continue;
            }

            $metaData = $cacheInstance->getMetadatas($cacheId);
            if ($input->getOption('filter-tag') !== null && !$this->isTagFiltered($metaData, $input)) {
                continue;
            }

            $row = array(
                $cacheId,
                date('Y-m-d H:i:s', $metaData['expire']),
            );
            if ($input->getOption('mtime')) {
                $row[] = date('Y-m-d H:i:s', $metaData['mtime']);
            }
            if ($input->getOption('tags')) {
                $row[] = implode(',', $metaData['tags']);
            }

            $table[] = $row;
        }

        $headers = array('ID', 'EXPIRE');
        if ($input->getOption('mtime')) {
            $headers[] = 'MTIME';
        }
        if ($input->getOption('tags')) {
            $headers[] = 'TAGS';
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders($headers)
            ->renderByFormat($output, $table, $input->getOption('format'));
    }
}
