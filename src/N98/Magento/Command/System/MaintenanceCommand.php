<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceCommand extends AbstractMagentoCommand
{
    const MAINTENANCE_FLAG_FILENAME = 'maintenance.flag';

    /**
     * Configured sys:maintenance command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('sys:maintenance')
            ->addOption('on', null, InputOption::VALUE_NONE, 'Enable maintenance mode')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
            ->setDescription('Toggles maintenance mode.')
        ;
    }

    /**
     * Run the command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);

        /**
         * false = disable maint
         * true  = enable main
         */
        $action = $input->getOption('off') ? false : ($input->getOption('on') ? true : !$this->isMaintenanceOn());

        if ($action) {
            $this->enable();
        } else {
            $this->disable();
        }
        $output->writeln(sprintf('Maintenance mode <info>%s</info>', $action ? 'on' : 'off'));
    }

    /**
     * Enable maintenance mode
     *
     * @return boolean
     */
    protected function enable()
    {
        if (!touch($this->_magentoRootFolder . DIRECTORY_SEPARATOR . self::MAINTENANCE_FLAG_FILENAME)) {
            throw new \RuntimeException('Failed to turn on maintenance mode');
        }
    }

    /**
     * Disable maintenance mode
     *
     * @return boolean
     */
    protected function disable()
    {
        if (!unlink($this->_magentoRootFolder . DIRECTORY_SEPARATOR . self::MAINTENANCE_FLAG_FILENAME)) {
            throw new \RuntimeException('Failed to turn off maintenance mode');
        }
    }

    /**
     * Determines if the store is currently in maintenance mode
     *
     * @return boolean
     */
    protected function isMaintenanceOn()
    {
        return file_exists($this->_magentoRootFolder . DIRECTORY_SEPARATOR . self::MAINTENANCE_FLAG_FILENAME);
    }
}
