<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use Exception;
use InvalidArgumentException;
use Mage;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OnCommand extends AbstractDependenciesCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:module:dependencies:on')
            ->addArgument('moduleName', InputArgument::REQUIRED, 'Module to show dependencies')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all dependencies (dependencies of dependencies)')
            ->setDescription('Show list of modules which given module depends on')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument('moduleName');
        $recursive = $input->getOption('all');

        if ($input->getOption('format') === null) {
            $this->writeSection($output, sprintf('List of module %s dependencies', $moduleName));
        }

        $this->detectMagento($output, true);
        $this->initMagento();

        try {
            $dependencies = $this->findModuleDependencies($moduleName, $recursive);
            if (!empty($dependencies)) {
                usort($dependencies, [$this, 'sortDependencies']);
            } else {
                $dependencies = [];
            }
            if ($input->getOption('format') === null && count($dependencies) === 0) {
                $output->writeln(sprintf("Module %s doesn't have dependencies", $moduleName));
            } else {
                $tableHelper = $this->getTableHelper();
                $tableHelper
                    ->setHeaders(['Name', 'Status', 'Current installed version', 'Code pool'])
                    ->renderByFormat($output, $dependencies, $input->getOption('format'));
            }
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
        return 0;
    }

    /**
     * Find dependencies of given module $moduleName.
     * If $recursive = true, dependencies will be collected recursively for all module dependencies
     *
     * @param string $moduleName
     * @param bool $recursive
     * @return array
     * @throws InvalidArgumentException
     */
    protected function findModuleDependencies(string $moduleName, bool $recursive = false): array
    {
        if ($this->modules === null) {
            $this->modules = Mage::app()->getConfig()->getNode('modules')->asArray();
        }

        if (isset($this->modules[$moduleName])) {
            $dependencies = [];
            $module = $this->modules[$moduleName];
            if (isset($module['depends']) && is_array($module['depends']) && count($module['depends']) > 0) {
                foreach (array_keys($module['depends']) as $dependencyName) {
                    if (isset($this->modules[$dependencyName])) {
                        $dependencies[] = [$dependencyName, isset($this->modules[$dependencyName]['active'])
                            ? $this->formatActive($this->modules[$dependencyName]['active'])
                            : '-', $this->modules[$dependencyName]['version']
                            ?? '-', $this->modules[$dependencyName]['codePool']
                            ?? '-'
                        ];
                        if ($recursive) {
                            $dependencies = array_merge(
                                $dependencies,
                                $this->findModuleDependencies($dependencyName, $recursive)
                            );
                        }
                    } else {
                        $dependencies[] = [$dependencyName, 'Not installed', '-', '-'];
                    }
                }
            }

            return $dependencies;
        } else {
            throw new InvalidArgumentException(sprintf("Module %s was not found", $moduleName));
        }
    }

    /**
     * Sort dependencies list by module name ascending
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function sortDependencies(array $a, array $b): int
    {
        return strcmp($a[0], $b[0]);
    }
}
