<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

use Exception;
use InvalidArgumentException;
use Mage_Core_Model_Config;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Simplexml_Element;

/**
 * Class AbstractDisableenableCommand
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
abstract class AbstractDisableenableCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_MODULE = 'moduleName';

    public const COMMAND_OPTION_CODEPOOL = 'codepool';

    /**
     * @var string
     */
    protected string $commandName = '';

    /**
     * @var Mage_Core_Model_Config
     */
    protected Mage_Core_Model_Config $mageConfig;

    /**
     * @var string
     */
    protected string $modulesDir;

    /**
     * Setup
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_MODULE,
                InputArgument::OPTIONAL,
                'Name of module to ' . $this->commandName
            )
            ->addOption(
                self::COMMAND_OPTION_CODEPOOL,
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of codePool to ' . $this->commandName
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
        $this->initMagento();

        $this->mageConfig = $this->_getMageConfig();
        $this->modulesDir = $this->mageConfig->getOptions()->getEtcDir() . DS . 'modules' . DS;

        /** @var string $codePool */
        $codePool = $input->getOption(self::COMMAND_OPTION_CODEPOOL);
        if ($codePool) {
            $output->writeln('<info>' . ($this->commandName == 'enable' ? 'Enabling' : 'Disabling') .
                ' modules in <comment>' . $codePool . '</comment> codePool...</info>');
            $this->enableCodePool($codePool, $output);

            return Command::SUCCESS;
        }

        /** @var string $module */
        $module = $input->getArgument(self::COMMAND_ARGUMENT_MODULE);
        if ($module) {
            $output->writeln('<info>' . ($this->commandName == 'enable' ? 'Enabling' : 'Disabling') .
                ' module <comment>' . $module . '</comment></info>');
            $this->enableModule($module, $output);

            return Command::SUCCESS;
        }

        throw new InvalidArgumentException('No code-pool option nor module-name argument');
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
        $modules = $this->mageConfig->getNode('modules');
        if ($modules) {
            foreach ($modules->asArray() as $module => $data) {
                if (isset($data['codePool']) && $data['codePool'] == $codePool) {
                    $this->enableModule($module, $output);
                }
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
            $content = file_get_contents($decFile);
            if ($content) {
                $xml = new Varien_Simplexml_Element($content);
                if ($xml->modules->{$module}) {
                    $validDecFile = $decFile;
                    break;
                }
            }
        }

        if (!$validDecFile) {
            $msg = sprintf(
                '<error><comment>%s: </comment>Couldn\'t find declaration file</error>',
                $module
            );
        } elseif (!is_writable($validDecFile)) {
            $msg = sprintf(
                '<error><comment>%s: </comment>Can\'t write to declaration file</error>',
                $module
            );
        } else {
            $setTo = $this->commandName == 'enable' ? 'true' : 'false';
            if ($xml && (string) $xml->modules->{$module}->active != $setTo) {
                $xml->modules->{$module}->active = $setTo;
                if (file_put_contents($validDecFile, $xml->asXML()) !== false) {
                    $msg = sprintf(
                        '<info><comment>%s: </comment>%sd</info>',
                        $module,
                        $this->commandName
                    );
                } else {
                    $msg = sprintf(
                        '<error><comment>%s: </comment>Failed to update declaration file [%s]</error>',
                        $module,
                        $validDecFile
                    );
                }
            } else {
                $msg = sprintf(
                    '<info><comment>%s: already %sd</comment></info>',
                    $module,
                    $this->commandName
                );
            }
        }

        $output->writeln($msg);
    }

    /**
     * Load module files in the opposite order to core Magento, so that we find the last loaded declaration
     * of a module first.
     *
     * @return array<int, string>
     */
    protected function getDeclaredModuleFiles(): array
    {
        $collectModuleFiles = [
            'base'   => [],
            'mage'   => [],
            'custom' => []
        ];

        $moduleDirs = glob($this->modulesDir . '*.xml');
        if ($moduleDirs) {
            foreach ($moduleDirs as $v) {
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
        }

        return array_reverse(array_merge(
            $collectModuleFiles['base'],
            $collectModuleFiles['mage'],
            $collectModuleFiles['custom']
        ));
    }
}
