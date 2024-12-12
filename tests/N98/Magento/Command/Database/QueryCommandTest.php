<?php

declare(strict_types=1);

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class QueryCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new QueryCommand());

        $command = $this->getApplication()->find('db:query');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'query'   => 'SHOW TABLES;'],
        );

        $this->assertStringContainsString('admin_user', $commandTester->getDisplay());
        $this->assertStringContainsString('catalog_product_entity', $commandTester->getDisplay());
        $this->assertStringContainsString('wishlist', $commandTester->getDisplay());
    }
}
