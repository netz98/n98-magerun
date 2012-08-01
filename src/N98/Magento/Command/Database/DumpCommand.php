<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:dump')
            ->addOption('only-command', 'Print only mysqldump command. Do not execute')
            ->setDescription('Dumps database with mysqldump cli client according to informations from local.xml')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $dialog = $this->getHelperSet()->get('dialog');
        $fileName = $dialog->ask($output, '<question>Filename for SQL dump:</question>', $this->dbSettings['dbname']);

        if (substr($fileName, -4, 4) !== '.sql') {
            $fileName .= '.sql';
        }

        $exec = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            $this->dbSettings['host'],
            $this->dbSettings['username'],
            $this->dbSettings['password'],
            $this->dbSettings['dbname'],
            $fileName
        );

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
        } else {
            $output->writeln('<info>Start dumping database: ' . $this->dbSettings['dbname'] . '</info>');
            exec($exec, $commandOutput, $returnValue);
            if ($returnValue > 0) {
                $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
            } else {
                $output->writeln('<info>Finished</info>');
            }
        }
    }

}