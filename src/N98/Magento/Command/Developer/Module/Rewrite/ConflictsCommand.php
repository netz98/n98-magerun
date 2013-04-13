<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\JUnitXml\Document as JUnitXmlDocument;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConflictsCommand extends AbstractRewriteCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:rewrite:conflicts')
            ->addOption('log-junit', null, InputOption::VALUE_REQUIRED, 'Log conflicts in JUnit XML format to defined file.')
            ->setDescription('Lists all magento rewrite conflicts')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $table = new \Zend_Text_Table(array('columnWidths' => array(8, 30, 60, 60)));
            $tableData = array();
            if ($this->initMagento()) {
                $time = microtime(true);
                $rewrites = $this->loadRewrites();
                $conflictCounter = 0;
                foreach ($rewrites as $type => $data) {
                    if (count($data) > 0 && is_array($data)) {
                        foreach ($data as $class => $rewriteClass) {
                            if (count($rewriteClass) > 1) {
                                if ($this->_isInheritanceConflict($rewriteClass)) {
                                    $tableData[] = array(
                                        'Type'         => $type,
                                        'Class'        => $class,
                                        'Rewrites'     => implode(', ', $rewriteClass),
                                        'Loaded Class' => $this->_getLoadedClass($type, $class),
                                    );
                                    $conflictCounter++;
                                }
                            }
                        }
                    }
                }

                if ($input->getOption('log-junit')) {
                    $this->logJUnit($tableData, $input->getOption('log-junit'), microtime($time) - $time);
                } else {
                    if ($conflictCounter > 0) {
                        $this->writeSection($output, 'Conflicts');
                        array_map(array($table, 'appendRow'), $tableData);
                        $output->write($table->render());
                        $output->writeln('<error>' . $conflictCounter . ' conflict' . ($conflictCounter > 1 ? 's' : '') . ' was found!</error>');
                    } else {
                        $output->writeln('<info>No rewrite conflicts was found.</info>');
                    }
                }
            }
        }
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
                return \Mage::getConfig()->getBlockClassName($class);

            case 'helpers':
                return \Mage::getConfig()->getHelperClassName($class);

            default:
            case 'models':
                return \Mage::getConfig()->getModelClassName($class);
                break;
        }
    }

    /**
     * @param array $conflicts
     * @param string $filename
     * @param float $duration
     */
    protected function logJUnit(array $conflicts, $filename, $duration)
    {
        $document = new JUnitXmlDocument();
        $suite = $document->addTestSuite();
        $suite->setName('n98-magerun: ' . $this->getName());
        $suite->setTimestamp(new \DateTime());
        $suite->setTime($duration);

        $testCase = $suite->addTestCase();
        $testCase->setName('Magento Rewrite Conflict Test');
        $testCase->setClassname('ConflictsCommand');
        if (count($conflicts) > 0) {
            foreach ($conflicts as $conflictRow) {
                $testCase->addFailure(
                    sprintf(
                        'Rewrite conflict: Type %s | Class: %s, Rewrites: %s | Loaded class: %s',
                        $conflictRow['Type'],
                        $conflictRow['Class'],
                        $conflictRow['Rewrites'],
                        $conflictRow['Loaded Class']
                    ),
                    'MagentoRewriteConflictException'
                );
            }
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
    protected function _isInheritanceConflict($classes)
    {
        $classes = array_reverse($classes);
        for ($i = 0; $i < count($classes) - 1; $i++) {
            try {
                $reflectionClass = new \ReflectionClass($classes[$i]);
                if ($reflectionClass->getParentClass()->getName() !== $classes[$i + 1]) {
                    return true;
                }
            } catch (\Exception $e) {
                return true;
            }
        }

        return false;
    }
}