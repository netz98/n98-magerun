<?php

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
