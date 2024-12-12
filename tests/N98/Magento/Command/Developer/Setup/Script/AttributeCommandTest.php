<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AttributeCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new AttributeCommand());
        $application->setAutoExit(false);

        $command = $this->getApplication()->find('dev:setup:script:attribute');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => 'sku'],
        );
        $this->assertStringContainsString("'type' => 'static',", $commandTester->getDisplay());
        $this->assertStringContainsString("Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'sku');", $commandTester->getDisplay());
    }
}
