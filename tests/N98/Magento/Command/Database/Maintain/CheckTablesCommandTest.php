<?php

namespace N98\Magento\Command\Database\Maintain;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @see \N98\Magento\Command\Database\Maintain\CheckTablesCommand
 */
class CheckTablesCommandTest extends TestCase
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
                '--table'  => 'catalogsearch_*',
            )
        );
        $this->assertContains('catalogsearch_fulltext,check,quick,OK', $commandTester->getDisplay());
        $timeRegex = '"\s+[0-9]+\srows","[0-9\.]+\ssecs"';
        $this->assertRegExp(
            '~catalogsearch_query,"ENGINE InnoDB",' . $timeRegex . '~',
            $commandTester->getDisplay()
        );
        $this->assertRegExp(
            '~catalogsearch_result,"ENGINE InnoDB",' . $timeRegex . '~',
            $commandTester->getDisplay()
        );
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
