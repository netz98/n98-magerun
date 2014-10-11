<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
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
            $allTypes = \Mage::app()->useCache();
            $typesToClean = $input->getArgument('type');

            foreach(array_keys($allTypes) as $type) {
                if (count($typesToClean) == 0 || in_array($type, $typesToClean)) {
                    \Mage::app()->getCacheInstance()->cleanType($type);
                    \Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $type));
                    $output->writeln('<info>' . $type . ' cache cleaned</info>');
                }
            }
        }
    }
}
