<?php

namespace N98\Magento\Command\Developer\Module;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\ArrayFunctions;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:list')
            ->addOption('codepool', null, InputOption::VALUE_OPTIONAL, 'Show modules in a specific codepool')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Show modules with a specific status')
            ->addOption('vendor', null, InputOption::VALUE_OPTIONAL, 'Show modules of a specified vendor')
            ->setAliases(array('sys:modules:list'))// deprecated
            ->setDescription('List all installed modules')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Magento Modules');
        }
        $this->initMagento();
        $modules = $this->findInstalledModules();
        $modules = $this->filterModules($modules, $input);

        if (empty($modules)) {
            $output->writeln("No modules match the specified criteria.");

            return;
        }

        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Code pool', 'Name', 'Version', 'Status'))
            ->renderByFormat($output, $modules, $input->getOption('format'));
    }

    /**
     * @return array
     */
    private function findInstalledModules()
    {
        $return = array();

        $modules = Mage::app()->getConfig()->getNode('modules')->asArray();
        foreach ($modules as $moduleName => $moduleInfo) {
            $codePool = isset($moduleInfo['codePool']) ? $moduleInfo['codePool'] : '';
            $version = isset($moduleInfo['version']) ? $moduleInfo['version'] : '';
            $active = isset($moduleInfo['active']) ? $moduleInfo['active'] : '';

            $return[] = array(
                'Code pool' => trim($codePool),
                'Name'      => trim($moduleName),
                'Version'   => trim($version),
                'Status'    => $this->formatActive($active),
            );
        }

        return $return;
    }

    /**
     * Filter modules by codepool, status and vendor if such options were inputted by user
     *
     * @param array $modules
     * @param InputInterface $input
     * @return array
     */
    private function filterModules(array $modules, InputInterface $input)
    {
        if ($input->getOption('codepool')) {
            $modules = ArrayFunctions::matrixFilterByValue($modules, "codePool", $input->getOption('codepool'));
        }

        if ($input->getOption('status')) {
            $modules = ArrayFunctions::matrixFilterByValue($modules, 'Status', $input->getOption('status'));
        }

        if ($input->getOption('vendor')) {
            $modules = ArrayFunctions::matrixFilterStartswith($modules, 'Name', $input->getOption('vendor'));
        }

        return $modules;
    }
}
