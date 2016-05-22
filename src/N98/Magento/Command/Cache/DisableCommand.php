<?php

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $codeArgument = BinaryString::trimExplodeEmpty(',', $input->getArgument('code'));
        $this->saveCacheStatus($codeArgument, false);

        if (empty($codeArgument)) {
            $this->_getCacheModel()->flush();
        } else {
            foreach ($codeArgument as $type) {
                $this->_getCacheModel()->cleanType($type);
            }
        }

        if (count($codeArgument) > 0) {
            foreach ($codeArgument as $code) {
                $output->writeln('<info>Cache <comment>' . $code . '</comment> disabled</info>');
            }
        } else {
            $output->writeln('<info>Caches disabled</info>');
        }
    }
}
