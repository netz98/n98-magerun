<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class QueryCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new QueryCommand());
        $command = $this->getApplication()->find('db:query');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'query'   => 'SHOW TABLES;',
            )
        );

        self::assertContains('admin_user', $commandTester->getDisplay());
        self::assertContains('catalog_product_entity', $commandTester->getDisplay());
        self::assertContains('wishlist', $commandTester->getDisplay());
    }
}
