<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Dependencies;

use Exception;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
abstract class AbstractDependenciesCommand extends AbstractCommand
{
     public const COMMAND_ARGUMENT_MODULE_NAME = 'moduleName';

     public const COMMAND_OPTION_ALL = 'all';

    /**#@+
     * Command texts to output
     *
     * @var string
     */
    public const COMMAND_SECTION_TITLE_TEXT = '';

    public const COMMAND_NO_RESULTS_TEXT = '';
    /**#@-*/

    /**
     * Array of magento modules found in config
     *
     * @var array<string, array<string, array<string, string>|string>>
     */
    protected array $modules;

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_MODULE_NAME,
                InputArgument::REQUIRED,
                'Module to show dependencies'
            )
            ->addOption(
                self::COMMAND_OPTION_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Show all dependencies (dependencies of dependencies)'
            )
            ->addFormatOption()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $modules = $this->_getMageConfigNode('modules')->asArray();
        ksort($modules);

        $this->modules = $modules;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function interact(InputInterface $input,OutputInterface $output): void
    {
        $moduleName = $input->getArgument(static::COMMAND_ARGUMENT_MODULE_NAME);

        if (is_null($moduleName)) {
            $dialog = $this->getQuestionHelper();
            $question = new ChoiceQuestion('<question>Please select a Module:</question> ', array_keys($this->modules));
            $moduleName =  $dialog->ask($input, $output, $question);
        }
        $input->setArgument(static::COMMAND_ARGUMENT_MODULE_NAME, $moduleName);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $moduleName */
        $moduleName = $input->getArgument(static::COMMAND_ARGUMENT_MODULE_NAME);
        /** @var bool $recursive */
        $recursive = $input->getOption(static::COMMAND_OPTION_ALL);

        if ($input->getOption(static::COMMAND_OPTION_FORMAT) === null) {
            $this->writeSection($output, sprintf(static::COMMAND_SECTION_TITLE_TEXT, $moduleName));
        }

        try {
            $dependencies = $this->findModuleDependencies($moduleName, $recursive);
            if (!empty($dependencies)) {
                usort($dependencies, [$this, 'sortDependencies']);
                /** @var string|null $format */
                $format = $input->getOption(static::COMMAND_OPTION_FORMAT);
                $tableHelper = $this->getTableHelper();
                $tableHelper
                    ->setHeaders(['Name', 'Status', 'Current installed version', 'Code pool'])
                    ->renderByFormat($output, $dependencies, $format);
            } else {
                if ($input->getOption(static::COMMAND_OPTION_FORMAT) === null) {
                    $output->writeln(sprintf(static::COMMAND_NO_RESULTS_TEXT, $moduleName));
                }

            }
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Find dependencies of given module $moduleName.
     *
     * If $recursive = true, dependencies will be collected recursively for all module dependencies
     *
     * @param string $moduleName
     * @param bool   $recursive  [optional]
     * @return array<int|string, array<int, mixed>>
     */
    abstract protected function findModuleDependencies(string $moduleName, bool $recursive = false): array;

    /**
     * Sort dependencies list by module name ascending
     *
     * @param string[] $a
     * @param string[] $b
     * @return int
     */
    private function sortDependencies(array $a, array $b)
    {
        return strcmp($a[0], $b[0]);
    }
}
