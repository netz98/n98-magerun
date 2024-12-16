<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Disable cache command
 *
 * @package N98\Magento\Command\Cache
 */
class DisableCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:disable')
            ->addArgument('code', InputArgument::OPTIONAL, 'Code of cache (Multiple codes sperated by comma)')
            ->setDescription('Disables magento caches')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $codeArgument = BinaryString::trimExplodeEmpty(',', (string) $input->getArgument('code'));
        $this->saveCacheStatus($codeArgument, false);

        if ($codeArgument === []) {
            $this->_getCacheModel()->flush();
        } else {
            foreach ($codeArgument as $type) {
                $this->_getCacheModel()->cleanType($type);
            }
        }

        if ($codeArgument !== []) {
            foreach ($codeArgument as $code) {
                $output->writeln('<info>Cache <comment>' . $code . '</comment> disabled</info>');
            }
        } else {
            $output->writeln('<info>Caches disabled</info>');
        }

        return Command::SUCCESS;
    }
}
