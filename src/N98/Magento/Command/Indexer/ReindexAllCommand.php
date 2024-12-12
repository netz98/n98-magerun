<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reindex all command
 *
 * @package N98\Magento\Command\Indexer
 */
class ReindexAllCommand extends AbstractIndexerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('index:reindex:all')
            ->setDescription('Reindex all magento indexes')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Loops all magento indexes and triggers reindex.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $this->disableObservers();

        $processes = $this->getIndexerModel()->getProcessesCollection();
        if (!$processes || !$this->executeProcesses($output, iterator_to_array($processes, false))) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
