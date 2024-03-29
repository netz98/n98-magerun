<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ReindexCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ReindexCommand());
        $command = $this->getApplication()->find('index:reindex');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'    => $command->getName(), 'index_code' => 'tag_summary,tag_summary']
        );

        self::assertStringContainsString('Successfully reindexed tag_summary', $commandTester->getDisplay());
    }
}
