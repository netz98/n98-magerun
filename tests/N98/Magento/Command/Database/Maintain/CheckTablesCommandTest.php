<?php

namespace N98\Magento\Command\Database\Maintain;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

/**
 * @see \N98\Magento\Command\Database\Maintain\CheckTablesCommand
 */
class CheckTablesTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getCommand();

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--format' => 'csv',
                '--type'   => 'quick',
                '--table'  => 'catalogsearch_*'
            )
        );
    
        $this->assertContains('catalogsearch_fulltext,check,QUICK', $commandTester->getDisplay());
        $this->assertContains('catalogsearch_result,check,QUICK', $commandTester->getDisplay());
    }

    /**
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function getCommand()
    {
        $application = $this->getApplication();
        $application->add(new CheckTablesCommand());
        $command = $this->getApplication()->find('db:maintain:check-tables');

        return $command;
    }
}
