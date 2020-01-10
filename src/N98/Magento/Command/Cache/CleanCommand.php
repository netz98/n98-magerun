<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clean')
            ->addArgument('type', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Cache type code like "config"')
            ->addOption(
                'reinit',
                null,
                InputOption::VALUE_NONE,
                'Reinitialise the config cache after cleaning'
            )
            ->addOption(
                'no-reinit',
                null,
                InputOption::VALUE_NONE,
                "Don't reinitialise the config cache after flushing"
            )
            ->setDescription('Clean magento cache')
        ;

        $help = <<<HELP
Cleans expired cache entries.

If you would like to clean only one cache type use like:

   $ n98-magerun.phar cache:clean full_page

If you would like to clean multiple cache types at once use like:

   $ n98-magerun.phar cache:clean full_page block_html

If you would like to remove all cache entries use `cache:flush`

Options:
    --reinit Reinitialise the config cache after cleaning (Default)
    --no-reinit Don't reinitialise the config cache after cleaning
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $noReinitOption = $input->getOption('no-reinit');
        if (!$noReinitOption) {
            $this->banUseCache();
        }

        $this->detectMagento($output, true);
        if (!$this->initMagento(true)) {
            return;
        }

        try {
            \Mage::app()->loadAreaPart('adminhtml', 'events');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $allTypes = \Mage::app()->getCacheInstance()->getTypes();
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

        if (!$noReinitOption) {
            $this->reinitCache();
        }
    }
}
