<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListExtensionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $this->markTestSkipped('Skip Test - Currently are connect problems. We skip test.');

        $this->getApplication()->initMagento();
        if (version_compare(\Mage::getVersion(), '1.4.2.0', '<=')) {
            $this->markTestSkipped('Skip Test - mage cli script does not exist.');
        }

        $application = $this->getApplication();
        $application->add(new ListExtensionsCommand());
        $command = $this->getApplication()->find('extension:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'search'  => 'Mage_All_Latest',
            )
        );

        $this->assertContains('Package', $commandTester->getDisplay());
        $this->assertContains('Version', $commandTester->getDisplay());
        $this->assertContains('Mage_All_Latest', $commandTester->getDisplay());
    }
}
