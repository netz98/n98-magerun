<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class InfoCommand extends AbstractDatabaseCommand
{
    protected function configure()
    {
        $this
            ->setName('db:info')
            ->addArgument('setting', InputArgument::OPTIONAL, 'Only output value of named setting')
            ->addDeprecatedAlias('database:info', 'Please use db:info')
            ->setDescription('Dumps database informations')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;

        $help = <<<HELP
This command is useful to print all informations about the current configured database in app/etc/local.xml.
It can print connection string for JDBC, PDO connections.
HELP;
        $this->setHelp($help);

    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectDbSettings($output);

        $settings = array();
        foreach ($this->dbSettings as $key => $value) {
            $settings[$key] = (string) $value;
        }

        $pdoConnectionString = '';
        if ($this->isSocketConnect) {
            $pdoConnectionString = sprintf(
                'mysql:unix_socket=%s;dbname=%s',
                $this->dbSettings['unix_socket'],
                $this->dbSettings['dbname']
            );
        } else {
            $pdoConnectionString = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $this->dbSettings['host'],
                isset($this->dbSettings['port']) ? $this->dbSettings['port'] : 3306,
                $this->dbSettings['dbname']
            );
        }
        $settings['PDO-Connection-String'] = $pdoConnectionString;

        $jdbcConnectionString = '';
        if ($this->isSocketConnect) {
            // isn't supported according to this post: http://stackoverflow.com/a/18493673/145829
            $jdbcConnectionString = 'Connecting using JDBC through a unix socket isn\'t supported!';
        } else {
            $jdbcConnectionString = sprintf(
                'jdbc:mysql://%s:%s/%s?username=%s&password=%s',
                $this->dbSettings['host'],
                isset($this->dbSettings['port']) ? $this->dbSettings['port'] : 3306,
                $this->dbSettings['dbname'],
                $this->dbSettings['username'],
                $this->dbSettings['password']
            );
        }
        $settings['JDBC-Connection-String'] = $jdbcConnectionString;

        $mysqlCliString = 'mysql ' . $this->getHelper('database')->getMysqlClientToolConnectionString();
        $settings['MySQL-Cli-String'] = $mysqlCliString;

        $rows = array();
        foreach ($settings as $settingName => $settingValue) {
            $rows[] = array($settingName, $settingValue);
        }

        if (($settingArgument = $input->getArgument('setting')) !== null) {
            if (!isset($settings[$settingArgument])) {
                throw new \InvalidArgumentException('Unknown setting: ' . $settingArgument);
            }
            $output->writeln((string) $settings[$settingArgument]);
        } else {
            $this->getHelper('table')
                ->setHeaders(array('Name', 'Value'))
                ->renderByFormat($output, $rows, $input->getOption('format'));
        }
    }

}
