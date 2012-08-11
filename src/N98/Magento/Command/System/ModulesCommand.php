<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModulesCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('system:modules:list')
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

        ksort($this->infos);
        foreach ($this->infos as $key => $value) {
            $output->writeln(str_pad($key, 40, ' ') . ': ' . $value);
        }
    }

    protected function findInstalledModules()
    {
        $modules = \Mage::app()->getConfig()->getNode('modules')->asArray();
        foreach ($modules as $moduleName => $moduleInfo) {
            $this->infos[$moduleInfo['codePool'] . ': ' . $moduleName] = str_pad($moduleInfo['version'], 12, ' ')
                                                                       . $this->formatActive($moduleInfo['active']);
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