<?php

namespace N98\Magento\Command\Eav\Attribute;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ViewCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('eav:attribute:view');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => 'sku']
        );

        self::assertStringContainsString('sku', $commandTester->getDisplay());
        self::assertStringContainsString('catalog_product_entity', $commandTester->getDisplay());
        self::assertStringContainsString('Backend-Type', $commandTester->getDisplay());
        self::assertStringContainsString('static', $commandTester->getDisplay());
    }
}
