<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Application;
use N98\Util\String;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnableCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:enable')
            ->addArgument('code', InputArgument::OPTIONAL, 'Code of cache (Multiple codes sperated by comma)')
            ->setDescription('Enables magento caches')
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
            $this->saveCacheStatus($codeArgument, true);

            if (count($codeArgument) > 0) {
                foreach ($codeArgument as $code) {
                    $output->writeln('<info>Cache <comment>' . $code . '</comment> enabled</info>');
                }
            } else {
                $output->writeln('<info>Caches enabled</info>');
            }
        }
    }
}