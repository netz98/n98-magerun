<?php

namespace N98\Magento\Command\Developer\Setup\Script;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class AttributeCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new AttributeCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('dev:setup:script:attribute');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                 'command'       => $command->getName(),
                 'entityType'    => 'catalog_product',
                 'attributeCode' => 'sku',
            )
        );
        $this->assertContains("'type' => 'static',", $commandTester->getDisplay());
        $this->assertContains(
            "Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', 'sku');",
            $commandTester->getDisplay()
        );
    }
}
