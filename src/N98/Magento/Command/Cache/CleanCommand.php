<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
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
            ->addArgument('type', InputArgument::OPTIONAL, 'Cache type code like "config"')
            ->setDescription('Clean magento cache')
        ;

        $help = <<<HELP
Cleans expired cache entries.
If you like to remove all entries use `cache:flush`
Or only one cache type like i.e. full_page cache:

   $ n98-magerun.phar cache:clean full_page

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
            $allTypes = \Mage::app()->useCache();
            foreach(array_keys($allTypes) as $type) {
                if ($input->getArgument('type') == '' || $input->getArgument('type') == $type) {
                    \Mage::app()->getCacheInstance()->cleanType($type);
                    $output->writeln('<info>' . $type . ' cache cleaned</info>');
                }
            }
        }
    }
}