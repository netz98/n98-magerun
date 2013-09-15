<?php

namespace N98\Magento\Command\Database;

use N98\Util\OperatingSystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this->setName('db:import')->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption('compression', 'c', InputOption::VALUE_REQUIRED, 'The compression of the specified file')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Print only mysql command. Do not execute')
            ->addOption('data-from-csv', 'd', InputOption::VALUE_NONE, 'Import table data from csv files')
            ->setDescription('Imports database with mysql cli client according to database defined in local.xml');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !OperatingSystem::isWindows();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $this->writeSection($output, 'Import MySQL Database');

        $fileName = $input->getArgument('filename');

        if (!file_exists($fileName)) {
            throw new \InvalidArgumentException('File does not exist');
        }

        $compressor = $this->getCompressor($input->getOption('compression'));

        $execs = array();
        if ($input->getOption('data-from-csv')) {
            $localInfile = $this->getMysqlVariableValue('local_infile');
            if (!$localInfile) {
                throw new \Exception('mysqld was started with --local-infile=0.');
            }

            if (!$this->mysqlUserHasPrivilege('FILE')) {
                throw new \Exception("Current mysql user doesn't have FILE privilege.");
            }

            $fileBaseName = basename($fileName);
            $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'n98';
            $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . $fileBaseName;

            $execs[] = 'rm -rf ' . $tmpDir;
            $execs[] = 'mkdir -p ' . $tmpDir;
            $execs[] = 'cp ' . $fileName . ' ' . $tmpFile;
            $execs[] = $compressor->getDecompressingCommand('mysql ' . $this->getMysqlClientToolConnectionString(),
                $tmpFile, false
            );

            $csvDataDir = $tmpDir . DIRECTORY_SEPARATOR . substr($fileBaseName, 0, -4)
                .  DumpCommand::CSV_DATA_FOLDER_SUFFIX;
            $execs = array_merge($execs, $this->getCsvDataFilesImportExecs($csvDataDir));
            $execs[] = 'rm -rf ' . $tmpDir;
        } else {
            // create import command
            $execs[] = $compressor->getDecompressingCommand('mysql ' . $this->getMysqlClientToolConnectionString(),
                $fileName
            );
        }

        $this->runExecs($input, $output, $fileName, $execs);
    }

    /**
     * Generate set of commands to import csv data files
     *
     * @param string $csvDataDir
     * @return array
     */
    private function getCsvDataFilesImportExecs($csvDataDir)
    {
        $mysqlConnectionString = $this->getMysqlClientToolConnectionString();

        $execs[] = 'NOW=$(date +"%T %d.%m.%Y"); echo "[${NOW}] Importing db dump..."';
        $execs[] = <<<SH
if [ -d "{$csvDataDir}" ]
then
    NOW=$(date +"%T %d.%m.%Y")

    shopt -s nullglob
    for filename in {$csvDataDir}/*.data.csv;
    do
        NOW=$(date +"%T %d.%m.%Y")
        echo "[\${NOW}] <- \$filename\c"
        time_start=`date +%s`

        table_name=`basename \$filename`
        table_name=\${table_name/.data.csv/}
        mysql --local-infile=1 {$mysqlConnectionString} <<EOF
            SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
            SET character_set_client = utf8;
            SET AUTOCOMMIT=0;
            SET UNIQUE_CHECKS=0;
            SET FOREIGN_KEY_CHECKS=0;
            SET NAMES 'utf8';

            LOAD DATA LOCAL INFILE '\$filename' INTO TABLE \$table_name
                FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
                LINES TERMINATED BY '\n';

            SET FOREIGN_KEY_CHECKS=1;
            SET UNIQUE_CHECKS=1;
            SET AUTOCOMMIT=1;
EOF

        time_end=`date +%s`
        time_elapsed=$((time_end - time_start))
        if [ \$time_elapsed -gt 0 ] ; then echo " [\$time_elapsed sec]" ; else echo ""; fi
    done;
fi
SH;

        return $execs;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $fileName
     * @param array $execs
     */
    protected function runExecs(InputInterface $input, OutputInterface $output, $fileName, array $execs)
    {
        if ($input->getOption('only-command')) {
            foreach ($execs as $exec) {
                $output->writeln($exec);
            }
        } else {
            if ($input->getOption('data-from-csv')) {
                $output->writeln('<comment>Start importing database <info>' . $this->dbSettings['dbname']
                    . '</info></comment>'
                );
                $output->writeln('Both table structure sql dump and csv data files will be '
                    . 'imported from the file <info>' . $fileName . '</info></comment>'
                );
            } else {
                $output->writeln('<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>'
                    . $this->dbSettings['dbname'] . '</info></comment>'
                );
            }

            foreach ($execs as $exec) {
                $commandOutput = '';
                exec($exec, $commandOutput, $returnValue);
                if ($returnValue > 0) {
                    $output->writeln('<error>' . implode(PHP_EOL, $commandOutput) . '</error>');
                    $output->writeln('<error>Return Code: ' . $returnValue . '. ABORTED.</error>');

                    return;
                } else {
                    $output->writeln('<comment>' . implode(PHP_EOL, $commandOutput)  . '</comment>');
                }
            }

            $output->writeln('<info>Finished</info>');
        }
    }

    /**
     * Generate help for data-from-csv option
     *
     * @return string
     */
    protected function getDataFromCsvHelp()
    {
        $messages = array();
        $messages[] = '';
        $messages[] = '<comment>data-from-csv option</comment>';
        $messages[] = ' Dramatically speedups import, depending on db size it can be 5,6 and even more times faster.';
        $messages[] = ' Requirements:';
        $messages[] = '     1. Db dump must be created using data-to-csv option';
        $messages[] = '     2. mysqld must be started without --local-infile=0';
        $messages[] = '     3. mysql user must have FILE privilege';
        $messages[] = ' For more info see LOAD DATA INFILE documentation: http://dev.mysql.com/doc/refman/5.1/en/load-data.html';

        return implode(PHP_EOL, $messages);
    }

    public function getHelp()
    {
        return parent::getHelp() . PHP_EOL
            . $this->getCompressionHelp() . PHP_EOL
            . $this->getDataFromCsvHelp();
    }
}
