<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Exception;
use Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flush cache command
 *
 * @package N98\Magento\Command\Cache
 */
class FlushCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setName('cache:flush')
            ->addOption(
                'reinit',
                null,
                InputOption::VALUE_NONE,
                'Reinitialise the config cache after flushing',
            )
            ->addOption(
                'no-reinit',
                null,
                InputOption::VALUE_NONE,
                "Don't reinitialise the config cache after flushing",
            )
            ->setDescription('Flush magento cache storage')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Flush the entire cache.

   $ n98-magerun.phar cache:flush [--reinit --no-reinit]

Options:
    --reinit Reinitialise the config cache after flushing (Default)
    --no-reinit Don't reinitialise the config cache after flushing
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        $noReinitOption = $input->getOption('no-reinit');
        if (!$noReinitOption) {
            $this->banUseCache();
        }

        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        try {
            Mage::app()->loadAreaPart('adminhtml', 'events');
        } catch (Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        }

        Mage::dispatchEvent('adminhtml_cache_flush_all', ['output' => $output]);
        $result = Mage::app()->getCacheInstance()->flush();
        if ($result) {
            $output->writeln('<info>Cache cleared</info>');
        } else {
            $output->writeln('<error>Failed to clear Cache</error>');
        }

        if (!$noReinitOption) {
            $this->reinitCache();
        }

        return Command::SUCCESS;
    }
}
