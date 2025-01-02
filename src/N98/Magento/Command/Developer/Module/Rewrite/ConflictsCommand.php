<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use Carbon\Carbon;
use Exception;
use Mage;
use N98\JUnitXml\Document as JUnitXmlDocument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Text_Table;

/**
 * List module conflicts command
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ConflictsCommand extends AbstractRewriteCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:module:rewrite:conflicts')
            ->addOption(
                'log-junit',
                null,
                InputOption::VALUE_REQUIRED,
                'Log conflicts in JUnit XML format to defined file.',
            )
            ->setDescription('Lists all magento rewrite conflicts');
    }

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $conflicts = [];
        $time = microtime(true);
        $rewrites = $this->loadRewrites();

        foreach ($rewrites as $type => $data) {
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $class => $rewriteClasses) {
                if (!$this->_isInheritanceConflict($rewriteClasses)) {
                    continue;
                }

                $conflicts[] = [
                    'Type'         => $type,
                    'Class'        => $class,
                    'Rewrites'     => implode(', ', $rewriteClasses),
                    'Loaded Class' => $this->_getLoadedClass($type, $class),
                ];
            }
        }

        if ($input->getOption('log-junit')) {
            $duration = microtime(true) - $time;
            $this->logJUnit($conflicts, $input->getOption('log-junit'), $duration);
        } else {
            $this->writeOutput($output, $conflicts);
        }

        return (int) (bool) $conflicts;
    }

    /**
     * Returns loaded class by type like models or blocks
     */
    protected function _getLoadedClass(string $type, string $class): string
    {
        switch ($type) {
            case 'blocks':
                return Mage::getConfig()->getBlockClassName($class);

            case 'helpers':
                return Mage::getConfig()->getHelperClassName($class);

            case 'models': // fall-through intended
            default:
                return Mage::getConfig()->getModelClassName($class);
        }
    }

    protected function logJUnit(array $conflicts, string $filename, float $duration): void
    {
        $document = new JUnitXmlDocument();
        $testSuiteElement = $document->addTestSuite();
        $testSuiteElement->setName('n98-magerun: ' . $this->getName());
        $testSuiteElement->setTimestamp(Carbon::now());
        $testSuiteElement->setTime($duration);

        $testCaseElement = $testSuiteElement->addTestCase();
        $testCaseElement->setName('Magento Rewrite Conflict Test');
        $testCaseElement->setClassname('ConflictsCommand');
        foreach ($conflicts as $conflict) {
            $message = sprintf(
                'Rewrite conflict: Type %s | Class: %s, Rewrites: %s | Loaded class: %s',
                $conflict['Type'],
                $conflict['Class'],
                $conflict['Rewrites'],
                $conflict['Loaded Class'],
            );
            $testCaseElement->addFailure($message, 'MagentoRewriteConflictException');
        }

        $document->save($filename);
    }

    /**
     * Check if rewritten class has inherited the parent class.
     * If yes we have no conflict. The top class can extend every core class.
     * So we cannot check this.
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
            } catch (Exception $exception) {
                return true;
            }

            $later = $earlier;
        }

        return false;
    }

    private function writeOutput(OutputInterface $output, array $conflicts): void
    {
        if ($conflicts === []) {
            $output->writeln('<info>No rewrite conflicts were found.</info>');
            return;
        }

        $number = count($conflicts);
        $zendTextTable = new Zend_Text_Table(['columnWidths' => [8, 30, 60, 60]]);

        array_map([$zendTextTable, 'appendRow'], $conflicts);
        $output->write($zendTextTable->render());
        $message = sprintf(
            '%d %s found!',
            $number,
            $number === 1 ? 'conflict was' : 'conflicts were',
        );

        $output->writeln('<error>' . $message . '</error>');
    }
}
