<?php

namespace N98\Magento\Command\Eav\Attribute;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('eav:attribute:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), '--filter-type' => 'catalog_product', '--add-source'  => true]
        );

        self::assertStringContainsString('eav/entity_attribute_source_boolean', $commandTester->getDisplay());
        self::assertStringContainsString('sku', $commandTester->getDisplay());
        self::assertStringContainsString('catalog_product', $commandTester->getDisplay());
    }
}
