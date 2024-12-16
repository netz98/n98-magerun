<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Enable cache command
 *
 * @package N98\Magento\Command\Cache
 */
class EnableCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:enable')
            ->addArgument('code', InputArgument::OPTIONAL, 'Code of cache (Multiple codes sperated by comma)')
            ->setDescription('Enables magento caches')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $codeArgument = BinaryString::trimExplodeEmpty(',', (string) $input->getArgument('code'));
        $this->saveCacheStatus($codeArgument, true);

        if ($codeArgument !== []) {
            foreach ($codeArgument as $code) {
                $output->writeln('<info>Cache <comment>' . $code . '</comment> enabled</info>');
            }
        } else {
            $output->writeln('<info>Caches enabled</info>');
        }

        return Command::SUCCESS;
    }
}
