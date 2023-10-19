<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractRewriteCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:rewrite:list')
            ->setDescription('Lists all magento rewrites')
            ->addFormatOption()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
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

        if (count($table) === 0 && $input->getOption('format') === null) {
            $output->writeln('<info>No rewrites were found.</info>');
        } else {
            if (count($table) == 0) {
                $table = [];
            }
            /* @var TableHelper $tableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(['Type', 'Class', 'Rewrite'])
                ->setRows($table)
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
        return 0;
    }
}
