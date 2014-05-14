<?php

namespace N98\Magento\Command\Database\Maintain;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class CheckTablesCommand extends AbstractMagentoCommand
{
    const MESSAGE_CHECK_NOT_SUPPORTED = 'The storage engine for the table doesn\'t support check';
    const MESSAGE_REPAIR_NOT_SUPPORTED = 'The storage engine for the table doesn\'t support repair';

    /**
     * @var array
     */
    protected $allowedTypes = array(
        'QUICK',
        'FAST',
        'CHANGED',
        'MEDIUM',
        'EXTENDED',
    );

    protected function configure()
    {
        $help = <<<'HELP'
<comment>TYPE OPTIONS</comment>

<info>QUICK</info>
            Do not scan the rows to check for incorrect links.
            Applies to InnoDB and MyISAM tables and views.
<info>FAST</info>
            Check only tables that have not been closed properly.
            Applies only to MyISAM tables and views; ignored for InnoDB.
<info>CHANGED</info>
            Check only tables that have been changed since the last check or that
            have not been closed properly. Applies only to MyISAM tables and views;
            ignored for InnoDB.
<info>MEDIUM</info>
            Scan rows to verify that deleted links are valid.
            This also calculates a key checksum for the rows and verifies this with a
            calculated checksum for the keys. Applies only to MyISAM tables and views;
            ignored for InnoDB.
<info>EXTENDED</info>
            Do a full key lookup for all keys for each row. This ensures that the table
            is 100% consistent, but takes a long time.
            Applies only to MyISAM tables and views; ignored for InnoDB.
HELP;


        $this
            ->setName('db:maintain:check-tables')
            ->setDescription('Check database tables')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Check type (one of QUICK, FAST, MEDIUM, EXTENDED, CHANGED)', 'MEDIUM')
            ->addOption('repair', null, InputOption::VALUE_NONE, 'Repair tables (only MyISAM)')
            ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'Process only given table (wildcards are supported)')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setHelp($help);
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \InvalidArgumentException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $type = strtoupper($type);
        if ($type && !in_array($type, $this->allowedTypes)) {
            throw new \InvalidArgumentException('Invalid type was given');
        }

        $this->detectMagento($output);
        $dbHelper = $this->getHelper('database'); /* @var $dbHelper \N98\Util\Console\Helper\DatabaseHelper */
        $connection = $dbHelper->getConnection($output);
        if ($input->getOption('table')) {
            $resolvedTables = array(
                $dbHelper->resolveTables(
                    array('@check'),
                    array(
                        'check' => array(
                            'tables' => $input->getOption('table')
                        )
                    )
                )
            );
            $tables = $resolvedTables[0];
        } else {
            $tables = $dbHelper->getTables();
        }

        $table = array();
        $progress = $this->getHelper('progress');
        $showProgress = $input->getOption('format') == null;
        if ($showProgress) {
            $progress->start($output, count($tables));
        }

        foreach ($tables as $tableName) {
            $result = $this->_checkTable($tableName, $type, $connection);
            if ($result['Msg_text'] == self::MESSAGE_CHECK_NOT_SUPPORTED) {
                if ($showProgress) {
                    $progress->advance();
                }

                continue;
            }

            $table[] = array(
                'table'     => $tableName,
                'operation' => $result['Op'],
                'type'      => $type,
                'status'    => $result['Msg_text'],
            );

            if ($result['Msg_text'] != 'OK'
                && $input->getOption('repair')
            ) {
                $result = $this->_repairTable($tableName, $type, $connection);
                if ($result['Msg_text'] != self::MESSAGE_REPAIR_NOT_SUPPORTED) {
                    $table[] = array(
                        'table'     => $tableName,
                        'operation' => $result['Op'],
                        'type'      => $type,
                        'status'    => $result['Msg_text'],
                    );
                }
            }

            if ($showProgress) {
                $progress->advance();
            }
        }

        if ($showProgress) {
            $progress->finish();
        }

        $this->getHelper('table')
            ->setHeaders(array('Table', 'Operation', 'Type', 'Status'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @param string $tableName
     * @param string $type
     * @param \PDO $connection
     * @return array
     */
    protected function _checkTable($tableName, $type, $connection)
    {
        $sql = sprintf('CHECK TABLE %s %s', $tableName, $type);
        $query = $connection->prepare($sql);
        $query->execute();
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param string $tableName
     * @param string $type
     * @param \PDO $connection
     * @return array
     */
    protected function _repairTable($tableName, $type, $connection)
    {
        $sql = sprintf('REPAIR TABLE %s %s', $tableName, $type);
        $query = $connection->prepare($sql);
        $query->execute();
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }
}