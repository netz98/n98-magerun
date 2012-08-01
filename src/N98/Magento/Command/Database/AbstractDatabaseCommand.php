<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

abstract class AbstractDatabaseCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $dbSettings;

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function detectDbSettings(OutputInterface $output)
    {
        $this->detectMagento($output);
        $configFile = $this->_magentoRootFolder . '/app/etc/local.xml';

        $config = simplexml_load_file($configFile);
        if (!$config->global->resources->default_setup->connection) {
            $output->writeln('<error>DB settings was not found in local.xml file</error>');
            return;
        }
        $this->dbSettings = (array)$config->global->resources->default_setup->connection;
    }
}