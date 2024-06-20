<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Mage_Index_Model_Resource_Process_Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Re-index all command
 *
 * @package N98\Magento\Command\Indexer
 */
class ReindexAllCommand extends AbstractIndexerCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'index:reindex:all';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Reindex all indexes.';

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
LLoops all indexes and triggers reindex.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $this->disableObservers();

        /* @var Mage_Index_Model_Resource_Process_Collection $processes */
        $processes = $this->getIndexerModel()->getProcessesCollection();

        /** @phpstan-ignore argument.type (@TODO(sr)) */
        if (!$this->executeProcesses($output, iterator_to_array($processes, false))) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
