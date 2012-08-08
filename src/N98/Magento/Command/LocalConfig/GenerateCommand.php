<?php

namespace N98\Magento\Command\LocalConfig;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('local-config:generate')
            ->setDescription('Generates local.xml config')
            ->addArgument('db-host', InputOption::VALUE_REQUIRED, 'Database host')
            ->addArgument('db-user', InputOption::VALUE_REQUIRED, 'Database user')
            ->addArgument('db-pass', InputOption::VALUE_REQUIRED, 'Database password')
            ->addArgument('db-name', InputOption::VALUE_REQUIRED, 'Database name')
            ->addArgument('session-save', InputOption::VALUE_REQUIRED, 'Session storage adapter')
            ->addArgument('admin-frontname', InputOption::VALUE_REQUIRED, 'Admin front name')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $configFile = $this->_magentoRootFolder . '/app/etc/local.xml';
        $configFileTemplate = $this->_magentoRootFolder . '/app/etc/local.xml.template';
        if (!file_exists($configFile)) {
            $this->writeSection($output, 'Generate Magento local.xml');
            $this->askForArguments($input, $output);
            if (!file_exists($configFileTemplate)) {
                $output->writeln('<error>File ' . $this->_magentoRootFolder . '/app/etc/local.xml.template does not exist.</error>');
                return;
            }

            if (!is_writable($this->_magentoRootFolder . '/app/etc')) {
                $output->writeln('<error>Folder ' . $this->_magentoRootFolder . '/app/etc is not writeable</error>');
                return;
            }

            $content = file_get_contents($configFileTemplate);

            $replace = array(
                '{{date}}'               => date(\DateTime::RFC2822),
                '{{key}}'                => md5(uniqid()),
                '{{db_prefix}}'          => '',
                '{{db_host}}'            => $input->getArgument('db-host'),
                '{{db_user}}'            => $input->getArgument('db-user'),
                '{{db_pass}}'            => $input->getArgument('db-pass'),
                '{{db_name}}'            => $input->getArgument('db-name'),
                '{{db_init_statemants}}' => 'SET NAMES utf8', // this is right -> magento has a little typo bug "statemants".
                '{{db_model}}'           => 'mysql4',
                '{{db_type}}'            => 'pdo_mysql',
                '{{db_pdo_type}}'        => '',
                '{{session_save}}'       => $input->getArgument('session-save'),
                '{{admin_frontname}}'    => $input->getArgument('admin-frontname'),
            );

            $newFileContent = str_replace(array_keys($replace), array_values($replace), $content);
            if (file_put_contents($this->_magentoRootFolder . '/app/etc/local.xml', $newFileContent)) {
                $output->writeln('<info>Generated config</info>');
            } else {
                $output->writeln('<error>could not save config</error>');
            }
        } else {
            $output->writeln('<info>local.xml file already exists in folder "' . $this->_magentoRootFolder . '/app/etc' . '"</info>');
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function askForArguments(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        // db-host
        if ($input->getArgument('db-host') === null) {
            $input->setArgument('db-host', $dialog->ask($output, '<question>Please enter the database host:</question>'));
        }
        if ($input->getArgument('db-host') === null) {
            $output->writeln('<error>db-host was not set.</error>');
            return;
        }

        // db-user
        if ($input->getArgument('db-user') === null) {
            $input->setArgument('db-user', $dialog->ask($output, '<question>Please enter the database username:</question>'));
        }
        if ($input->getArgument('db-user') === null) {
            $output->writeln('<error>db-user was not set.</error>');
            return;
        }

        // db-pass
        if ($input->getArgument('db-pass') === null) {
            $input->setArgument('db-pass', $dialog->ask($output, '<question>Please enter the database password:</question>'));
        }

        // db-name
        if ($input->getArgument('db-name') === null) {
            $input->setArgument('db-name', $dialog->ask($output, '<question>Please enter the database name:</question>'));
        }
        if ($input->getArgument('db-name') === null) {
            $output->writeln('<error>db-name was not set.</error>');
            return;
        }

        // session-save
        if ($input->getArgument('session-save') === null) {
            $input->setArgument('session-save', $dialog->ask($output, '<question>Please enter the session save:</question>', 'files'));
        }
        if ($input->getArgument('session-save') === null) {
            $output->writeln('<error>session-save was not set.</error>');
            return;
        }

        // admin-frontname
        if ($input->getArgument('admin-frontname') === null) {
            $input->setArgument('admin-frontname', $dialog->ask($output, '<question>Please enter the admin frontname:</question>', 'admin'));
        }
        if ($input->getArgument('admin-frontname') === null) {
            $output->writeln('<error>admin-frontname was not set.</error>');
            return;
        }
    }
}