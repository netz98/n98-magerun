<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('dev:module:list')
            ->addOption('codepool', null, InputOption::VALUE_OPTIONAL, 'Show modules in a specific codepool')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Show modules with a specific status')
            ->addOption('vendor', null, InputOption::VALUE_OPTIONAL, 'Show modules of a specified vendor')
            ->setAliases(array('sys:modules:list')) // deprecated
            ->setDescription('List all installed modules');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Magento Modules');
        $this->initMagento();
        $this->findInstalledModules();
        $this->filterModules($input);

        if ( ! empty($this->infos)) {
            $this->getHelper('table')
                ->setHeaders('codePool', 'Name', 'Version', 'Status')
                ->setRows($this->infos)
                ->render($output);
        } else {
            $output->writeln("No modules match the specified criteria.");
        }
    }

    protected function findInstalledModules()
    {
        $modules = \Mage::app()->getConfig()->getNode('modules')->asArray();
        foreach ($modules as $moduleName => $moduleInfo) {
            $this->infos[] = array(
                $moduleInfo['codePool'],
                $moduleName,
                isset($moduleInfo['version']) ? $moduleInfo['version'] : '',
                $this->formatActive($moduleInfo['active']),
            );
        }
    }

    protected function filterModules($input)
    {
        if ($input->getOption('codepool')) {
            $this->filterByField("codePool", $input->getOption('codepool'));
        }

        if ($input->getOption('status')) {
            $this->filterByField('Status', $input->getOption('status'));
        }

        if ($input->getOption('vendor')) {
            $this->filterByFieldStartsWith('Name', $input->getOption('vendor'));
        }
    }

    /**
     * @param string $field
     * @param strnig $value
     */
    protected function filterByField($field, $value)
    {
        foreach ($this->infos as $k => $info) {
            if ($info[$field] != $value) {
                unset($this->infos[$k]);
            }
        }
    }

    /**
     * @param string $field
     * @param strnig $value
     */
    protected function filterByFieldStartsWith($field, $value)
    {
        foreach ($this->infos as $k => $info) {
            if (strncmp($info[$field], $value, strlen($value))) {
                unset($this->infos[$k]);
            }
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function formatActive($value)
    {
        if (in_array($value, array(1, 'true'))) {
            return 'active';
        }

        return 'inactive';
    }
}
