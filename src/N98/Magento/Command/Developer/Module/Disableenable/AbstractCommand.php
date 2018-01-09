<?php

namespace N98\Magento\Command\Developer\Module\Disableenable;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
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
            ->addArgument('moduleName', InputArgument::OPTIONAL, 'Name of module to ' . $this->commandName)
            ->addOption('codepool', null, InputOption::VALUE_OPTIONAL, 'Name of codePool to ' . $this->commandName)
            ->setDescription(ucwords($this->commandName) . ' a module or all modules in codePool');
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (false === $this->initMagento()) {
            throw new RuntimeException('Magento could not be loaded');
        }
        $this->config = \Mage::getConfig();
        $this->modulesDir = $this->config->getOptions()->getEtcDir() . DS . 'modules' . DS;
        if ($codePool = $input->getOption('codepool')) {
            $output->writeln('<info>' . ($this->commandName == 'enable' ? 'Enabling' : 'Disabling') .
                ' modules in <comment>' . $codePool . '</comment> codePool...</info>');
            $this->enableCodePool($codePool, $output);
        } elseif ($module = $input->getArgument('moduleName')) {
            $this->enableModule($module, $output);
        } else {
            throw new InvalidArgumentException('No code-pool option nor module-name argument');
        }
    }

    /**
     * Search a code pool for modules and enable them
     *
     * @param string $codePool
     * @param OutputInterface $output
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
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function enableModule($module, OutputInterface $output)
    {
        $validDecFile = false;
        foreach ($this->getDeclaredModuleFiles() as $decFile) {
            $xml = new \Varien_Simplexml_Element(file_get_contents($decFile));
            if ($xml->modules->{$module}) {
                $validDecFile = $decFile;
                break;
            }
        }

        if (!$validDecFile) {
            $msg = sprintf('<error><comment>%s: </comment>Couldn\'t find declaration file</error>', $module);
        } elseif (!is_writable($validDecFile)) {
            $msg = sprintf('<error><comment>%s: </comment>Can\'t write to declaration file</error>', $module);
        } else {
            $setTo = $this->commandName == 'enable' ? 'true' : 'false';
            if ((string) $xml->modules->{$module}->active != $setTo) {
                $xml->modules->{$module}->active = $setTo;
                if (file_put_contents($validDecFile, $xml->asXML()) !== false) {
                    $msg = sprintf('<info><comment>%s: </comment>%sd</info>', $module, $this->commandName);
                } else {
                    $msg = sprintf(
                        '<error><comment>%s: </comment>Failed to update declaration file [%s]</error>', $module, $validDecFile
                    );
                }
            } else {
                $msg = sprintf('<info><comment>%s: already %sd</comment></info>', $module, $this->commandName);
            }
        }

        $output->writeln($msg);
    }

    /**
     * Load module files in the opposite order to core Magento, so that we find the last loaded declaration
     * of a module first.
     *
     * @return array
     */
    protected function getDeclaredModuleFiles()
    {
        $collectModuleFiles = array(
            'base'   => array(),
            'mage'   => array(),
            'custom' => array(),
        );

        foreach (glob($this->modulesDir . '*.xml')  as $v) {
            $name = explode(DIRECTORY_SEPARATOR, $v);
            $name = substr($name[count($name) - 1], 0, -4);

            if ($name == 'Mage_All') {
                $collectModuleFiles['base'][] = $v;
            } elseif (substr($name, 0, 5) == 'Mage_') {
                $collectModuleFiles['mage'][] = $v;
            } else {
                $collectModuleFiles['custom'][] = $v;
            }
        }

        return array_reverse(array_merge(
            $collectModuleFiles['base'],
            $collectModuleFiles['mage'],
            $collectModuleFiles['custom']
        ));
    }
}
