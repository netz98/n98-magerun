<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clean')
            ->addArgument('type', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Cache type code like "config"')
            ->setDescription('Clean magento cache')
        ;

        $help = <<<HELP
Cleans expired cache entries.

If you would like to clean only one cache type use like:

   $ n98-magerun.phar cache:clean full_page

If you would like to clean multiple cache types at once use like:

   $ n98-magerun.phar cache:clean full_page block_html

If you would like to remove all cache entries use `cache:flush`

HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->banUseCache();
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            \Mage::app()->loadAreaPart('adminhtml', 'events');
            $allTypes = \Mage::app()->useCache();
            $typesToClean = $input->getArgument('type');
            $this->validateCacheCodes($typesToClean);
            $typeKeys = array_keys($allTypes);

            foreach ($typeKeys as $type) {
                if (count($typesToClean) == 0 || in_array($type, $typesToClean)) {
                    \Mage::app()->getCacheInstance()->cleanType($type);
                    \Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $type));
                    $output->writeln('<info>Cache <comment>' . $type . '</comment> cleaned</info>');
                }
            }

            $this->reinitCache();
        }
    }
}
