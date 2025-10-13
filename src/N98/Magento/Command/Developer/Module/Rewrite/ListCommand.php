<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List module rewrites command
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ListCommand extends AbstractRewriteCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:module:rewrite:list')
            ->setDescription('Lists all magento rewrites')
            ->addFormatOption()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $rewrites = array_merge($this->loadRewrites(), $this->loadAutoloaderRewrites());

        $table = [];
        foreach ($rewrites as $type => $data) {
            if ((is_countable($data) ? count($data) : 0) > 0) {
                foreach ($data as $class => $rewriteClass) {
                    $table[] = [$type, $class, implode(', ', $rewriteClass)];
                }
            }
        }

        if ($table === [] && $input->getOption('format') === null) {
            $output->writeln('<info>No rewrites were found.</info>');
        } else {
            if ($table === []) {
                $table = [];
            }

            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['Type', 'Class', 'Rewrite'])
                ->setRows($table)
                ->renderByFormat($output, $table, $input->getOption('format'));
        }

        return Command::SUCCESS;
    }
}
