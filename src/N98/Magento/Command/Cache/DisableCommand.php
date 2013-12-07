<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Util\String;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DisableCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:disable')
            ->addArgument('code', InputArgument::OPTIONAL, 'Code of cache (Multiple codes sperated by comma)')
            ->setDescription('Disables magento caches')
        ;
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
            $codeArgument = String::trimExplodeEmpty(',', $input->getArgument('code'));
            $this->saveCacheStatus($codeArgument, false);

            if (empty($codeArgument)) {
                $this->_getCacheModel()->flush();
            } else {
                foreach ($codeArgument as $type) {
                    $this->_getCacheModel()->cleanType($type);
                }
            }

            $output->writeln('<info>Caches disabled</info>');
        }
    }
}