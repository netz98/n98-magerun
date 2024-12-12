<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database\Maintain;

use Symfony\Component\Console\Command\Command;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @see \N98\Magento\Command\Database\Maintain\CheckTablesCommand
 */
final class CheckTablesCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->getCommand();

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--format' => 'csv', '--type'   => 'quick', '--table'  => 'catalogsearch_*'],
        );
        $this->assertStringContainsString('catalogsearch_fulltext,check,quick,OK', $commandTester->getDisplay());
        $timeRegex = '"\s+[0-9]+\srows","[0-9\.]+\ssecs"';
        $this->assertMatchesRegularExpression('~catalogsearch_query,"ENGINE InnoDB",' . $timeRegex . '~', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression('~catalogsearch_result,"ENGINE InnoDB",' . $timeRegex . '~', $commandTester->getDisplay());
    }

    /**
     * @return Command
     */
    private function getCommand()
    {
        $application = $this->getApplication();
        $application->add(new CheckTablesCommand());

        return $this->getApplication()->find('db:maintain:check-tables');
    }
}
