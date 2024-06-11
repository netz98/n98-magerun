<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

use Exception;
use InvalidArgumentException;
use Mage;
use Mage_Core_Model_Config;
use N98\Magento\Command\AbstractMagentoCommand;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Simplexml_Element;

/**
 * Abstract enable/disable Magento module(s)
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
class AbstractCommand extends AbstractMagentoCommand
{
    public const COMMAND_ARGUMENT_MODULE = 'moduleName';

    public const COMMAND_OPTION_CODEPOOL = 'codepool';

    /**
     * @var Mage_Core_Model_Config
     */
    protected $config;

    /**
     * @var string
     */
    protected string $modulesDir;

    /**
     * Setup
     *
     * @return void
     */
    protected function configure()
    {
        $action = $this->getCommandAction();

        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_MODULE,
                InputArgument::OPTIONAL,
                'Name of module to ' . $action
            )
            ->addOption(
                self::COMMAND_OPTION_CODEPOOL,
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of codePool to ' . $action
            )
        ;
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            throw new RuntimeException('Application could not be loaded');
        }

        $this->config = Mage::getConfig();
        $this->modulesDir = $this->config->getOptions()->getEtcDir() . DS . 'modules' . DS;

        if ($codePool = $input->getOption(self::COMMAND_OPTION_CODEPOOL)) {
            $output->writeln('<info>' . ($this->getCommandAction() == 'enable' ? 'Enabling' : 'Disabling') .
                ' modules in <comment>' . $codePool . '</comment> codePool...</info>');
            $this->enableCodePool($codePool, $output);
        } elseif ($module = $input->getArgument(self::COMMAND_ARGUMENT_MODULE)) {
            $this->enableModule($module, $output);
        } else {
            throw new InvalidArgumentException('No code-pool option nor module-name argument');
        }

        return Command::SUCCESS;
    }

    /**
     * Search a code pool for modules and enable them
     *
     * @param string $codePool
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function enableCodePool(string $codePool, OutputInterface $output): void
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
     * @throws Exception
     */
    protected function enableModule(string $module, OutputInterface $output): void
    {
        $xml = null;
        $validDecFile = false;
        foreach ($this->getDeclaredModuleFiles() as $decFile) {
            $xml = new Varien_Simplexml_Element(file_get_contents($decFile));
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
            $setTo = $this->getCommandAction() == 'enable' ? 'true' : 'false';
            if ((string) $xml->modules->{$module}->active != $setTo) {
                $xml->modules->{$module}->active = $setTo;
                if (file_put_contents($validDecFile, $xml->asXML()) !== false) {
                    $msg = sprintf('<info><comment>%s: </comment>%sd</info>', $module, $this->getCommandAction());
                } else {
                    $msg = sprintf(
                        '<error><comment>%s: </comment>Failed to update declaration file [%s]</error>',
                        $module,
                        $validDecFile
                    );
                }
            } else {
                $msg = sprintf('<info><comment>%s: already %sd</comment></info>', $module, $this->getCommandAction());
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
    protected function getDeclaredModuleFiles(): array
    {
        $collectModuleFiles = [
            'base'   => [],
            'mage'   => [],
            'custom' => []
        ];

        foreach (glob($this->modulesDir . '*.xml') as $v) {
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

    /**
     * @return string
     */
    protected function getCommandAction(): string
    {
        return substr(static::$defaultName, strrpos(static::$defaultName, ':') + 1);
    }
}
