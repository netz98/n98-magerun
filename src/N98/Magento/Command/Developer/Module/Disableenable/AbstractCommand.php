<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

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
 * Class AbstractCommand
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
class AbstractCommand extends AbstractMagentoCommand
{
    protected Mage_Core_Model_Config $modulesConfig;

    protected string $modulesDir;

    protected string $commandName;

    protected function configure(): void
    {
        $this
            ->setName('dev:module:' . $this->commandName)
            ->addArgument('moduleName', InputArgument::OPTIONAL, 'Name of module to ' . $this->commandName)
            ->addOption('codepool', null, InputOption::VALUE_OPTIONAL, 'Name of codePool to ' . $this->commandName)
            ->setDescription(ucwords($this->commandName) . ' a module or all modules in codePool');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (false === $this->initMagento()) {
            throw new RuntimeException('Magento could not be loaded');
        }

        $this->modulesConfig = Mage::getConfig();
        $this->modulesDir = $this->modulesConfig->getOptions()->getEtcDir() . DS . 'modules' . DS;
        if ($codePool = $input->getOption('codepool')) {
            $output->writeln('<info>' . ($this->commandName === 'enable' ? 'Enabling' : 'Disabling') .
                ' modules in <comment>' . $codePool . '</comment> codePool...</info>');
            $this->enableCodePool($codePool, $output);
        } elseif ($module = $input->getArgument('moduleName')) {
            $this->enableModule($module, $output);
        } else {
            throw new InvalidArgumentException('No code-pool option nor module-name argument');
        }

        return Command::SUCCESS;
    }

    /**
     * Search a code pool for modules and enable them
     */
    protected function enableCodePool(string $codePool, OutputInterface $output): void
    {
        $modulesNode = $this->modulesConfig->getNode('modules');
        if ($modulesNode) {
            $modules = $modulesNode->asArray();
            foreach ($modules as $module => $data) {
                if (isset($data['codePool']) && $data['codePool'] == $codePool) {
                    $this->enableModule($module, $output);
                }
            }
        }
    }

    /**
     * Enable a single module
     */
    protected function enableModule(string $module, OutputInterface $output): void
    {
        $xml = null;
        $validDecFile = false;
        foreach ($this->getDeclaredModuleFiles() as $declaredModuleFile) {
            $content = file_get_contents($declaredModuleFile);
            if ($content) {
                $xml = new Varien_Simplexml_Element($content);
                if ($xml->modules->{$module}) {
                    $validDecFile = $declaredModuleFile;
                    break;
                }
            }
        }

        if (!$validDecFile) {
            $msg = sprintf("<error><comment>%s: </comment>Couldn't find declaration file</error>", $module);
        } elseif (!is_writable($validDecFile)) {
            $msg = sprintf("<error><comment>%s: </comment>Can't write to declaration file</error>", $module);
        } else {
            $setTo = $this->commandName === 'enable' ? 'true' : 'false';
            if ((string) $xml->modules->{$module}->active !== $setTo) {
                $xml->modules->{$module}->active = $setTo;
                if (file_put_contents($validDecFile, $xml->asXML()) !== false) {
                    $msg = sprintf('<info><comment>%s: </comment>%sd</info>', $module, $this->commandName);
                } else {
                    $msg = sprintf(
                        '<error><comment>%s: </comment>Failed to update declaration file [%s]</error>',
                        $module,
                        $validDecFile,
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
     */
    protected function getDeclaredModuleFiles(): array
    {
        $collectModuleFiles = [
            'base'   => [],
            'mage'   => [],
            'custom' => [],
        ];

        $paths = glob($this->modulesDir . '*.xml');
        if ($paths) {
            foreach ($paths as $path) {
                $name = explode(DIRECTORY_SEPARATOR, $path);
                $name = substr($name[count($name) - 1], 0, -4);

                if ($name === 'Mage_All') {
                    $collectModuleFiles['base'][] = $path;
                } elseif (substr($name, 0, 5) === 'Mage_') {
                    $collectModuleFiles['mage'][] = $path;
                } else {
                    $collectModuleFiles['custom'][] = $path;
                }
            }
        }

        return array_reverse(array_merge(
            $collectModuleFiles['base'],
            $collectModuleFiles['mage'],
            $collectModuleFiles['custom'],
        ));
    }
}
