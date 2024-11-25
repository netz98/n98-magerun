<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List index command
 *
 * @package N98\Magento\Command\Indexer
 */
class ListCommand extends AbstractIndexerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('index:list')
            ->setDescription('Lists all magento indexes')
            ->addFormatOption()
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
Lists all Magento indexers of current installation.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $table = [];
        foreach ($this->getIndexerList() as $index) {
            $table[] = [$index['code'], $index['status'], $index['last_runtime']];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['code', 'status', 'time'])
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }
}
