<?php

namespace N98\Magento\Command\Indexer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ReindexAllCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ReindexAllCommand());
        $command = $this->getApplication()->find('index:reindex:all');

        $application->initMagento();

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()]
        );

        self::assertStringContainsString('Successfully reindexed catalog_product_attribute', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalog_product_price', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalog_url', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalog_product_flat', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalog_category_flat', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalog_category_product', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed catalogsearch_fulltext', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed cataloginventory_stock', $commandTester->getDisplay());
        self::assertStringContainsString('Successfully reindexed tag_summary', $commandTester->getDisplay());
    }
}
