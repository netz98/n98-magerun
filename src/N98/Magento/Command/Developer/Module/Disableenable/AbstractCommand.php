<?php

namespace N98\Magento\Command\Developer\Module\Disableenable;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Enable disable Magento module(s)
 */
class AbstractCommand extends AbstractMagentoCommand
{
    /**
     * @var Mage_Core_Model_Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $modulesDir;

    /**
     * @var string
     */
    protected $commandName;

    /**
     * Setup
     * 
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('dev:module:' . $this->commandName)
            ->addArgument('moduleName', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Name of module to ' . $this->commandName)
            ->addOption('codepool', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Name of codePool to ' . $this->commandName)
            ->setDescription(ucwords($this->commandName) . ' a module or all modules in codePool');
    }

    /**
     * Execute command
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (false === $this->initMagento()) {
            throw new \RuntimeException('Magento could not be loaded');
        }
        $this->config     = \Mage::getConfig();
        $this->modulesDir = $this->config->getOptions()->getEtcDir() . DS . 'modules' . DS;
        if ($codePool = $input->getOption('codepool')) {
            $output->writeln('<info>' . ($this->commandName == 'enable' ? 'Enabling' : 'Disabling') .
                ' modules in <comment>' . $codePool . '</comment> codePool...</info>');
            $this->enableCodePool($codePool, $output);
        } else if ($module = $input->getArgument('moduleName')) {
            $this->enableModule($module, $output);
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Search a code pool for modules and enable them
     * 
     * @param string $codePool
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * 
     * @return int|void
     */
    protected function enableCodePool($codePool, OutputInterface $output)
    {
        $modules = $this->config->getNode('modules')->asArray();
        foreach ($modules as $module => $data) {
            if (isset($data['codePool']) && $data['codePool'] == $codePool) {
                $this->enableModule($module, $output);
            }
        }
    }    

    /**
     * Enable a single module
     * 
     * @param string $module
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * 
     * @return int|void
     */
    protected function enableModule($module, OutputInterface $output)
    {
        $decFile = $this->modulesDir . $module . '.xml';
        if (!is_file($decFile)) {
            $output->writeln('<error><comment>' . $module . ': </comment>Couldn\'t find declaration file</error>');
        } else if (!is_writable($decFile)) {
            $output->writeln('<error><comment>' . $module . ': </comment>Can\'t write to declaration file</error>');
        } else {
            $xml = new \Varien_Simplexml_Element(file_get_contents($decFile));
            $setTo = $this->commandName == 'enable' ? 'true' : 'false';
            if ((string)$xml->modules->{$module}->active != $setTo) {
                $xml->modules->{$module}->active = $setTo;
                if (file_put_contents($decFile, $xml->asXML()) !== false) {
                    $output->writeln('<info><comment>' . $module . ': </comment>' . $this->commandName . 'd</info>');
                } else {
                    $output->writeln('<error><comment>' . $module . ': </comment>Failed to update declaration file</error>');
                }
            } else {
                $output->writeln('<info><comment>' . $module . ': already ' . $this->commandName . 'd</comment></info>');
            }
        }
    }
}
