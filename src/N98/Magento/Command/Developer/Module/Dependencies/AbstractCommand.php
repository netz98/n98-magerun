<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Dependencies;

use Exception;
use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractCommand
 *
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
abstract class AbstractCommand extends AbstractMagentoCommand
{
    /**#@+
     * Command texts to output
     *
     * @var string
     */
    public const COMMAND_NAME = '';

    public const COMMAND_DESCRIPTION = '';

    public const COMMAND_SECTION_TITLE_TEXT = '';

    public const COMMAND_NO_RESULTS_TEXT = '';

    /**#@-*/

    /**
     * Array of magento modules found in config
     */
    protected ?array $modules = null;

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->addArgument('moduleName', InputArgument::REQUIRED, 'Module to show dependencies')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all dependencies (dependencies of dependencies)')
            ->setDescription(static::COMMAND_DESCRIPTION)
            ->addFormatOption()
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = $input->getArgument('moduleName');
        $recursive = $input->getOption('all');
        if ($input->getOption('format') === null) {
            $this->writeSection($output, sprintf(static::COMMAND_SECTION_TITLE_TEXT, $moduleName));
        }

        $this->detectMagento($output, true);
        $this->initMagento();

        try {
            $dependencies = $this->findModuleDependencies($moduleName, $recursive);
            if ($dependencies !== []) {
                usort($dependencies, [$this, 'sortDependencies']);
                $tableHelper = $this->getTableHelper();
                $tableHelper
                    ->setHeaders(['Name', 'Status', 'Current installed version', 'Code pool'])
                    ->renderByFormat($output, $dependencies, $input->getOption('format'));
            } else {
                $output->writeln(sprintf(static::COMMAND_NO_RESULTS_TEXT, $moduleName));
            }
        } catch (Exception $exception) {
            $output->writeln($exception->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Find dependencies of given module $moduleName.
     *
     * If $recursive = true, dependencies will be collected recursively for all module dependencies
     *
     * @throws InvalidArgumentException of module-name is not found
     */
    abstract protected function findModuleDependencies(string $moduleName, bool $recursive = false): array;

    /**
     * Sort dependencies list by module name ascending
     */
    private function sortDependencies(array $a, array $b): int
    {
        return strcmp($a[0], $b[0]);
    }
}
