<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
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
            ->addOption('filter-tag', '', InputOption::VALUE_OPTIONAL, 'Filter output by TAG (seperate multiple tags by comma)')
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
            $cacheInstance = \Mage::app()->getCache();
            /* @var $cacheInstance \Varien_Cache_Core */
            $cacheIds = $cacheInstance->getIds();
            $table = array();
            foreach ($cacheIds as $cacheId) {
                if ($input->getOption('filter-id') !== null) {
                    if (!stristr($cacheId, $input->getOption('filter-id'))) {
                        continue;
                    }
                }

                $metaData = $cacheInstance->getMetadatas($cacheId);
                if ($input->getOption('filter-tag') !== null) {
                    if (count(array_intersect($metaData['tags'], explode(',', $input->getOption('filter-tag')))) <= 0) {
                        continue;
                    }
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

            $this->getHelper('table')
                ->setHeaders($headers)
                ->setRows($table)->render($output);
        }
    }
}