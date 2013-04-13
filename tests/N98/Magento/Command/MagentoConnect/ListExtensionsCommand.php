<?php

namespace N98\Magento\Command\MagentoConnect;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ListExtensionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListExtensionsCommand());
        $command = $this->getApplication()->find('extension:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'search'  => 'Mage_All_Latest'
            )
        );
    
        $this->assertContains('Package', $commandTester->getDisplay());
        $this->assertContains('Version', $commandTester->getDisplay());
        $this->assertContains('Mage_All_Latest', $commandTester->getDisplay());
    }
}