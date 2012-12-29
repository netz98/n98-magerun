<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:import')
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysql command. Do not execute')
            ->setDescription('Imports database with mysql cli client according to database defined in local.xml');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $this->writeSection($output, 'Import MySQL Database');

        $fileName = $input->getArgument('filename');

        if (!file_exists($fileName)) {
            throw new \InvalidArgumentException('SQL file does not exist');
        }

        // dump data for all other tables
        $exec = 'mysql ' . $this->getMysqlClientToolConnectionString() . ' < ' . escapeshellarg($fileName);

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
        } else {
            // Import
            $output->writeln('<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>' . $this->dbSettings['dbname'] . '</info>');
            exec($exec, $commandOutput, $returnValue);
            if ($returnValue > 0) {
                $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
            }
            $output->writeln('<info>Finished</info>');
        }
    }

}