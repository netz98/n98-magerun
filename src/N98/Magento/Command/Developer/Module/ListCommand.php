<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Modules;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Magento Modules');
        }

        $this->initMagento();

        $modules = $this->filterModules($input);

        if (count($modules) === 0) {
            $output->writeln('No modules match the specified criteria.');
            return Command::FAILURE;
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['codePool', 'Name', 'Version', 'Status'])
            ->renderByFormat($output, iterator_to_array($modules), $input->getOption('format'));

        return Command::SUCCESS;
    }

    private function filterModules(InputInterface $input): Modules
    {
        $modules = new Modules();
        return $modules
            ->findInstalledModules()
            ->filterModules($input);
    }
}
