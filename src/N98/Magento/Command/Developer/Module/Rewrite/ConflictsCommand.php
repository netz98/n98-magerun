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
        $this->writeSection($output, 'Conflicts');
        if ($this->initMagento()) {
            $rewrites = $this->loadRewrites();
            $table = array();
            $conflictCounter = 0;
            foreach ($rewrites as $type => $data) {
                if (count($data) > 0) {
                    foreach ($data as $class => $rewriteClass) {
                        if (count($rewriteClass) > 1) {
                            $table[] = array(
                                'Type'         => $type,
                                'Class'        => $class,
                                'Rewrites'     => implode(', ', $rewriteClass),
                                'Loaded Class' => \Mage::getConfig()->getModelClassName($class),
                            );
                            $conflictCounter++;
                        }
                    }
                }
            }

            if (count($table) > 0) {
                $output->writeln('<error>' . $conflictCounter . ' conflict' . ($conflictCounter > 1 ? 's' : '') . ' was found!</error>');
                $this->getHelper('table')->write($output, $table);
            } else {
                $output->writeln('<info>No rewrite conflicts was found.</info>');
            }

        }
    }
}