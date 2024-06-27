<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use DateTime;
use Exception;
use N98\JUnitXml\Document as JUnitXmlDocument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Text_Table;
use Zend_Text_Table_Exception;

/**
 * List rewrite conflicts command
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ConflictsCommand extends AbstractRewriteCommand
{
    protected const COMMAND_OPTION_LOG_JUNIT = 'log-junit';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:rewrite:conflicts';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all rewrite conflicts.';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_LOG_JUNIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Log conflicts in JUnit XML format to defined file.'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Lists all duplicated rewrites and tells you which class is loaded by Magento.
The command checks class inheritance in order of your module dependencies.

* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.

Exit status is 0 if no conflicts were found, 1 if conflicts were found and 2 if there was a problem to
initialize Magento.
HELP;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int exit code: 0 no conflicts found, 1 conflicts found, 2 magento could not be initialized
     * @throws Zend_Text_Table_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conflicts = [];
        $time = microtime(true);
        $rewrites = $this->loadRewrites();

        foreach ($rewrites as $type => $data) {
            if (!is_array($data)) {
                continue;
            }
            /**
             * @var string $class
             * @var string[] $rewriteClasses
             */
            foreach ($data as $class => $rewriteClasses) {
                if (!$this->_isInheritanceConflict($rewriteClasses)) {
                    continue;
                }

                $conflicts[] = [
                    'Type'         => $type,
                    'Class'        => $class,
                    'Rewrites'     => implode(', ', $rewriteClasses),
                    'Loaded Class' => $this->_getLoadedClass($type, $class)
                ];
            }
        }

        $logJunit = $input->getOption(self::COMMAND_OPTION_LOG_JUNIT);
        if (is_string($logJunit)) {
            $duration = microtime(true) - $time;
            $this->logJUnit($conflicts, $logJunit, $duration);
        } else {
            $this->writeOutput($output, $conflicts);
        }

        return (int) (bool) $conflicts;
    }

    /**
     * Returns loaded class by type like models or blocks
     *
     * @param string $type
     * @param string $class
     * @return string
     */
    protected function _getLoadedClass(string $type, string $class): string
    {
        switch ($type) {
            case 'blocks':
                return $this->_getMageConfig()->getBlockClassName($class);

            case 'helpers':
                return $this->_getMageConfig()->getHelperClassName($class);

            case 'models': // fall-through intended
            default:
                /** @noinspection PhpParamsInspection */
                return $this->_getMageConfig()->getModelClassName($class);
        }
    }

    /**
     * @param array<int, array<string, string>> $conflicts
     * @param string $filename
     * @param float $duration
     */
    protected function logJUnit(array $conflicts, string $filename, float $duration): void
    {
        $document = new JUnitXmlDocument();
        $suite = $document->addTestSuite();
        $suite->setName('n98-magerun: ' . $this->getName());
        $suite->setTimestamp(new DateTime());
        $suite->setTime($duration);

        $testCase = $suite->addTestCase();
        $testCase->setName('Magento Rewrite Conflict Test');
        $testCase->setClassname('ConflictsCommand');
        foreach ($conflicts as $conflict) {
            $message = sprintf(
                'Rewrite conflict: Type %s | Class: %s, Rewrites: %s | Loaded class: %s',
                $conflict['Type'],
                $conflict['Class'],
                $conflict['Rewrites'],
                $conflict['Loaded Class']
            );
            $testCase->addFailure($message, 'MagentoRewriteConflictException');
        }

        $document->save($filename);
    }

    /**
     * Check if rewritten class has inherited the parent class.
     * If yes we have no conflict. The top class can extend every core class.
     * So we cannot check this.
     *
     * @param array<int, string> $classes
     * @return bool
     */
    protected function _isInheritanceConflict(array $classes): bool
    {
        $later = null;
        foreach (array_reverse($classes) as $class) {
            $earlier = ClassUtil::create($class);
            try {
                if ($later instanceof ClassUtil
                    && $later->exists()
                    && $earlier->exists()
                    && !$later->isA($earlier)
                ) {
                    return true;
                }
            } catch (Exception $e) {
                return true;
            }
            $later = $earlier;
        }

        return false;
    }

    /**
     * @param OutputInterface $output
     * @param array<int, array<string, int|string>> $conflicts
     * @throws Zend_Text_Table_Exception
     */
    private function writeOutput(OutputInterface $output, array $conflicts): void
    {
        if (!$conflicts) {
            $output->writeln('<info>No rewrite conflicts were found.</info>');
            return;
        }

        $number = count($conflicts);
        $table = new Zend_Text_Table(['columnWidths' => [8, 30, 60, 60]]);

        array_map([$table, 'appendRow'], $conflicts);
        $output->write($table->render());
        $message = sprintf(
            '%d %s found!',
            $number,
            $number === 1 ? 'conflict was' : 'conflicts were'
        );

        $output->writeln('<error>' . $message . '</error>');
    }
}
