<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Modules;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List modules command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:list')
            ->addOption('codepool', null, InputOption::VALUE_OPTIONAL, 'Show modules in a specific codepool')
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Show modules with a specific status')
            ->addOption('vendor', null, InputOption::VALUE_OPTIONAL, 'Show modules of a specified vendor')
            ->setAliases(['sys:modules:list'])// deprecated
            ->setDescription('List all installed modules')
            ->addFormatOption();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Magento Modules');
        }
        $this->initMagento();

        $modules = $this->filterModules($input);

        if (!count($modules)) {
            $output->writeln("No modules match the specified criteria.");
            return 0;
        }

        $table = $this->getTableHelper();
        $table
            ->setHeaders(['codePool', 'Name', 'Version', 'Status'])
            ->renderByFormat($output, iterator_to_array($modules), $input->getOption('format'));
        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return Modules
     */
    private function filterModules(InputInterface $input)
    {
        $modules = new Modules();
        $modules = $modules->findInstalledModules()
            ->filterModules($input);

        return $modules;
    }
}
