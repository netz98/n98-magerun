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
            array(
                'command'       => $command->getName(),
                'entityType'    => 'catalog_product',
                'attributeCode' => 'sku',
            )
        );

        self::assertContains('sku', $commandTester->getDisplay());
        self::assertContains('catalog_product_entity', $commandTester->getDisplay());
        self::assertContains('Backend-Type', $commandTester->getDisplay());
        self::assertContains('static', $commandTester->getDisplay());
    }
}
