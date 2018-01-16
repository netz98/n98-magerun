<?php

namespace N98\Magento\Command\Database;

use InvalidArgumentException;
use N98\Util\Exec;
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
            ->addOption('compression', 'c', InputOption::VALUE_REQUIRED, 'The compression of the specified file')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysql command. Do not execute')
            ->addOption('only-if-empty', null, InputOption::VALUE_NONE, 'Imports only if database is empty')
            ->addOption(
                'optimize',
                null,
                InputOption::VALUE_NONE,
                'Convert verbose INSERTs to short ones before import (not working with compression)'
            )
            ->addOption('drop', null, InputOption::VALUE_NONE, 'Drop and recreate database before import')
            ->addOption('stdin', null, InputOption::VALUE_NONE, 'Import data from STDIN rather than file')
            ->addOption('drop-tables', null, InputOption::VALUE_NONE, 'Drop tables before import')
            ->setDescription('Imports database with mysql cli client according to database defined in local.xml');

        $help = <<<HELP
Imports an SQL file with mysql cli client into current configured database.

You need to have MySQL client tools installed on your system.
HELP;
        $this->setHelp($help);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Exec::allowed();
    }

    /**
     * Optimize a dump by converting single INSERTs per line to INSERTs with multiple lines
     * @param $fileName
     * @return string temporary filename
     */
    protected function optimize($fileName)
    {
        $in = fopen($fileName, 'r');
        $result = tempnam(sys_get_temp_dir(), 'dump') . '.sql';
        $out = fopen($result, 'w');

        fwrite($out, 'SET autocommit=0;' . "\n");
        $currentTable = '';
        $maxlen = 8 * 1024 * 1024; // 8 MB
        $len = 0;
        while ($line = fgets($in)) {
            if (strtolower(substr($line, 0, 11)) == 'insert into') {
                preg_match('/^insert into `(.*)` \([^)]*\) values (.*);/i', $line, $m);

                if (count($m) < 3) { // fallback for very long lines or other cases where the preg_match fails
                    if ($currentTable != '') {
                        fwrite($out, ";\n");
                    }
                    fwrite($out, $line);
                    $currentTable = '';
                    continue;
                }

                $table = $m[1];
                $values = $m[2];

                if ($table != $currentTable || ($len > $maxlen - 1000)) {
                    if ($currentTable != '') {
                        fwrite($out, ";\n");
                    }
                    $currentTable = $table;
                    $insert = 'INSERT INTO `' . $table . '` VALUES ' . $values;
                    fwrite($out, $insert);
                    $len = strlen($insert);
                } else {
                    fwrite($out, ',' . $values);
                    $len += strlen($values) + 1;
                }
            } else {
                if ($currentTable != '') {
                    fwrite($out, ";\n");
                    $currentTable = '';
                }
                fwrite($out, $line);
            }
        }

        fwrite($out, ";\n");

        fwrite($out, 'COMMIT;' . "\n");

        fclose($in);
        fclose($out);

        return $result;
    }
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $this->writeSection($output, 'Import MySQL Database');
        $dbHelper = $this->getHelper('database');

        $fileName = $this->checkFilename($input);

        $compressor = $this->getCompressor($input->getOption('compression'));

        if ($input->getOption('optimize')) {
            if ($fileName === '-') {
                throw new InvalidArgumentException('Option --optimize not compatible with STDIN import');
            }
            if ($input->getOption('only-command')) {
                throw new InvalidArgumentException('Options --only-command and --optimize are not compatible');
            }
            if ($input->getOption('compression')) {
                throw new InvalidArgumentException('Options --compression and --optimize are not compatible');
            }
            $output->writeln('<comment>Optimizing <info>' . $fileName . '</info> to temporary file');
            $fileName = $this->optimize($fileName);
        }

        // create import command
        $exec = 'mysql ' . $dbHelper->getMysqlClientToolConnectionString();
        if ($fileName !== '-') {
            $exec = $compressor->getDecompressingCommand($exec, $fileName);
        }

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
            return;
        } else {
            if ($input->getOption('only-if-empty')
                && count($dbHelper->getTables()) > 0
            ) {
                $output->writeln('<comment>Skip import. Database is not empty</comment>');

                return;
            }
        }

        if ($input->getOption('drop')) {
            $dbHelper->dropDatabase($output);
            $dbHelper->createDatabase($output);
        }
        if ($input->getOption('drop-tables')) {
            $dbHelper->dropTables($output);
        }

        $this->doImport($output, $fileName, $exec);

        if ($input->getOption('optimize')) {
            unlink($fileName);
        }
    }

    public function asText()
    {
        return parent::asText() . "\n" .
            $this->getCompressionHelp();
    }

    /**
     * @param InputInterface $input
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function checkFilename(InputInterface $input)
    {
        if ($input->getOption('stdin')) {
            return '-';
        }
        $fileName = $input->getArgument('filename');
        if (!file_exists($fileName)) {
            throw new InvalidArgumentException('File does not exist');
        }
        return $fileName;
    }

    /**
     * @param OutputInterface $output
     * @param string          $fileName
     * @param string          $exec
     *
     * @return void
     */
    protected function doImport(OutputInterface $output, $fileName, $exec)
    {
        $returnValue = null;
        $commandOutput = null;
        $output->writeln(
            '<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>'
            . $this->dbSettings['dbname'] . '</info>'
        );

        Exec::run($exec, $commandOutput, $returnValue);

        if ($returnValue != 0) {
            $output->writeln('<error>' . $commandOutput . '</error>');
        }
        $output->writeln('<info>Finished</info>');
    }
}
