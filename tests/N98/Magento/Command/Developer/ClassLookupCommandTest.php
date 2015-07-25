<?php

namespace N98\Magento\Command\Developer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ClassLookupCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ClassLookupCommand());
        $command = $this->getApplication()->find('dev:class:lookup');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'type'    => 'model',
                'name'    => 'catalog/product',
            )
        );
    
        $this->assertRegExp('/Mage_Catalog_Model_Product/', $commandTester->getDisplay());
    }
}
