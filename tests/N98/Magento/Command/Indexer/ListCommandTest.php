<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('index:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        // check if i.e. at least one index is listed
        $this->assertRegExp('/catalog_product_flat/', $commandTester->getDisplay());
    }
}
