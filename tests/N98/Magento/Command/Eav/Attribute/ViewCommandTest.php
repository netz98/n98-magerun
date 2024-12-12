<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ViewCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());

        $command = $this->getApplication()->find('eav:attribute:view');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => 'sku'],
        );

        $this->assertStringContainsString('sku', $commandTester->getDisplay());
        $this->assertStringContainsString('catalog_product_entity', $commandTester->getDisplay());
        $this->assertStringContainsString('Backend-Type', $commandTester->getDisplay());
        $this->assertStringContainsString('static', $commandTester->getDisplay());
    }
}
