<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use InvalidArgumentException;
use N98\Util\Exec;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import database command
 *
 * @package N98\Magento\Command\Database
 */
class ImportCommand extends AbstractDatabaseCommand
{
    protected function configure(): void
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
                'Convert verbose INSERTs to short ones before import (not working with compression)',
            )
            ->addOption('drop', null, InputOption::VALUE_NONE, 'Drop and recreate database before import')
            ->addOption('stdin', null, InputOption::VALUE_NONE, 'Import data from STDIN rather than file')
            ->addOption('drop-tables', null, InputOption::VALUE_NONE, 'Drop tables before import')
            ->setDescription('Imports database with mysql cli client according to database defined in local.xml');
    }

    public function getHelp(): string
    {
        $help = <<<HELP
Imports an SQL file with mysql cli client into current configured database.

You need to have MySQL client tools installed on your system.
HELP;
        return
            $help . PHP_EOL
            . $this->getCompressionHelp() . PHP_EOL;
    }

    public function isEnabled(): bool
    {
        return Exec::allowed();
    }

    /**
     * Optimize a dump by converting single INSERTs per line to INSERTs with multiple lines
     */
    protected function optimize(string $fileName): string
    {
        $result = tempnam(sys_get_temp_dir(), 'dump') . '.sql';

        $in = fopen($fileName, 'r');
        if (!$in) {
            return $result;
        }

        $out = fopen($result, 'w');
        if (!$out) {
            return $result;
        }

        fwrite($out, 'SET autocommit=0;' . "\n");
        $currentTable = '';
        $maxLen = 8 * 1024 * 1024; // 8 MB
        $len = 0;
        while ($line = fgets($in)) {
            if (strtolower(substr($line, 0, 11)) === 'insert into') {
                preg_match('/^insert into `(.*)` \([^)]*\) values (.*);/i', $line, $m);

                if (count($m) < 3) { // fallback for very long lines or other cases where the preg_match fails
                    if ($currentTable !== '') {
                        fwrite($out, ";\n");
                    }

                    fwrite($out, $line);
                    $currentTable = '';
                    continue;
                }

                $table = $m[1];
                $values = $m[2];

                if ($table !== $currentTable || ($len > $maxLen - 1000)) {
                    if ($currentTable !== '') {
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
                if ($currentTable !== '') {
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectDbSettings($output);

        $this->writeSection($output, 'Import MySQL Database');
        $databaseHelper = $this->getDatabaseHelper();

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
        $exec = 'mysql ' . $databaseHelper->getMysqlClientToolConnectionString();
        if ($fileName !== '-') {
            $exec = $compressor->getDecompressingCommand($exec, $fileName);
        }

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
            return Command::SUCCESS;
        }

        if ($input->getOption('only-if-empty')
            && (is_countable($databaseHelper->getTables()) ? count($databaseHelper->getTables()) : 0) > 0) {
            $output->writeln('<comment>Skip import. Database is not empty</comment>');
            return Command::SUCCESS;
        }

        if ($input->getOption('drop')) {
            $databaseHelper->dropDatabase($output);
            $databaseHelper->createDatabase($output);
        }

        if ($input->getOption('drop-tables')) {
            $databaseHelper->dropTables($output);
        }

        $this->doImport($output, $fileName, $exec);

        if ($input->getOption('optimize')) {
            unlink($fileName);
        }

        return Command::SUCCESS;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function checkFilename(InputInterface $input): string
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

    protected function doImport(OutputInterface $output, string $fileName, string $exec): void
    {
        $returnValue = null;
        $commandOutput = null;
        $output->writeln(
            '<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>'
            . $this->dbSettings['dbname'] . '</info>',
        );

        Exec::run($exec, $commandOutput, $returnValue);

        if ($returnValue != 0) {
            $output->writeln('<error>' . $commandOutput . '</error>');
        }

        $output->writeln('<info>Finished</info>');
    }
}
