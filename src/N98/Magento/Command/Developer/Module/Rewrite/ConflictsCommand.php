<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use DateTime;
use Exception;
use Mage;
use N98\JUnitXml\Document as JUnitXmlDocument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Text_Table;

class ConflictsCommand extends AbstractRewriteCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:rewrite:conflicts')
            ->addOption(
                'log-junit',
                null,
                InputOption::VALUE_REQUIRED,
                'Log conflicts in JUnit XML format to defined file.'
            )
            ->setDescription('Lists all magento rewrite conflicts');

        $help = <<<HELP
Lists all duplicated rewrites and tells you which class is loaded by Magento.
The command checks class inheritance in order of your module dependencies.

* If a filename with `--log-junit` option is set the tool generates an XML file and no output to *stdout*.

Exit status is 0 if no conflicts were found, 1 if conflicts were found and 2 if there was a problem to
initialize Magento.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int exit code: 0 no conflicts found, 1 conflicts found, 2 magento could not be initialized
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 2;
        }

        $conflicts = array();
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

                if ($this->_isARewriteFixingTheConflict($rewriteClasses, $class)) {
                    continue;
                }

                $conflicts[] = array(
                    'Type'         => $type,
                    'Class'        => $class,
                    'Rewrites'     => implode(', ', $rewriteClasses),
                    'Loaded Class' => $this->_getLoadedClass($type, $class),
                );
            }
        }

        if ($input->getOption('log-junit')) {
            $duration = microtime($time) - $time;
            $this->logJUnit($conflicts, $input->getOption('log-junit'), $duration);
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
    protected function _getLoadedClass($type, $class)
    {
        switch ($type) {
            case 'blocks':
                return Mage::getConfig()->getBlockClassName($class);

            case 'helpers':
                return Mage::getConfig()->getHelperClassName($class);

            case 'models': // fall-through intended
            default:
                /** @noinspection PhpParamsInspection */
                return Mage::getConfig()->getModelClassName($class);
        }
    }

    /**
     * @param array  $conflicts
     * @param string $filename
     * @param float  $duration
     */
    protected function logJUnit(array $conflicts, $filename, $duration)
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
     * @var array $classes
     * @return bool
     */
    protected function _isInheritanceConflict(array $classes)
    {
        $later = null;
        foreach (array_reverse($classes) as $class) {
            $earlier = ClassUtil::create($class);
            try {
                if (
                    $later instanceof ClassUtil
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
     * Check if one of the rewritten classes actually is written to fix the conflict.
     * We can check by checking for an annotation in the PHPDOC block (@SolvesRewriteConflict)
     *
     * @var array $rewriteClasses
     * @var string $rewrittenClass
     * @return bool
     */
    protected function _isARewriteFixingTheConflict($rewriteClasses, $rewrittenClass)
    {
        foreach ($rewriteClasses as $rewriteClass) {
            $r = new \ReflectionClass($rewriteClass);
            $doc = $r->getDocComment();
            preg_match_all('#@(.*?)\n#s', $doc, $annotations);

            foreach ($annotations as $annotation) {
                if (stripos($annotation[0], 'SolvesRewriteConflict') === 0) {
                    preg_match('/SolvesRewriteConflict\((.*)\)/', $annotation[0], $matches);

                    $classesInAnnotation = array_map('trim', explode(',', $matches[1]));
                    $firstClassInAnnotation = array_shift($classesInAnnotation);
                    $rewriteClassesWithoutCurrentClass = array_diff($rewriteClasses, array($rewriteClass));

                    /* Check if all the conflicted classes are actually mentioned in the annotation,
                     * another conflict could later have been introduced.
                     */
                    if (count($classesInAnnotation) > 1
                        && $firstClassInAnnotation == $rewrittenClass
                        && count(array_diff($rewriteClassesWithoutCurrentClass, $classesInAnnotation)) === 0
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param OutputInterface $output
     * @param array           $conflicts
     * @return int
     */
    private function writeOutput(OutputInterface $output, array $conflicts)
    {
        if (!$conflicts) {
            $output->writeln('<info>No rewrite conflicts were found.</info>');
            return;
        }

        $number = count($conflicts);
        $table = new Zend_Text_Table(array('columnWidths' => array(8, 30, 60, 60)));

        array_map(array($table, 'appendRow'), $conflicts);
        $output->write($table->render());
        $message = sprintf(
            '%d %s found!',
            $number,
            $number === 1 ? 'conflict was' : 'conflicts were'
        );

        $output->writeln('<error>' . $message . '</error>');
    }
}
