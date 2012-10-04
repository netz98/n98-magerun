<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\AbstractMagentoCommand;
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
            $this->writeSection($output, 'Conflicts');
            $table = new \Zend_Text_Table(array('columnWidths' => array(8, 30, 60, 60)));
            if ($this->initMagento()) {
                $rewrites = $this->loadRewrites();
                $conflictCounter = 0;
                foreach ($rewrites as $type => $data) {
                    if (count($data) > 0) {
                        foreach ($data as $class => $rewriteClass) {
                            if (count($rewriteClass) > 1) {
                                if ($this->_isInheritanceConflict($rewriteClass)) {
                                    $table->appendRow(array(
                                        'Type'         => $type,
                                        'Class'        => $class,
                                        'Rewrites'     => implode(', ', $rewriteClass),
                                        'Loaded Class' => \Mage::getConfig()->getModelClassName($class),
                                    ));
                                    $conflictCounter++;
                                }
                            }
                        }
                    }
                }

                if ($conflictCounter > 0) {
                    $output->writeln('<error>' . $conflictCounter . ' conflict' . ($conflictCounter > 1 ? 's' : '') . ' was found!</error>');
                    $output->write($table->render());
                } else {
                    $output->writeln('<info>No rewrite conflicts was found.</info>');
                }

            }
        }
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
        rsort($classes);
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