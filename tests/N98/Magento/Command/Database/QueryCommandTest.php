<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

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
    
        $this->assertContains('admin_user', $commandTester->getDisplay());
        $this->assertContains('catalog_product_entity', $commandTester->getDisplay());
        $this->assertContains('wishlist', $commandTester->getDisplay());
    }
}
