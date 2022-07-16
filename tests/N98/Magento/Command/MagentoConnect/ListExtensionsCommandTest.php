<?php

namespace N98\Magento\Command\MagentoConnect;

use Mage;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListExtensionsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = null;
        $command = null;
        $commandTester = null;
        self::markTestSkipped('Skip Test - Currently are connect problems. We skip test.');

        $this->getApplication()->initMagento();
        if (version_compare(Mage::getVersion(), '1.4.2.0', '<=')) {
            self::markTestSkipped('Skip Test - mage cli script does not exist.');
        }

        $application = $this->getApplication();
        $application->add(new ListExtensionsCommand());
        $command = $this->getApplication()->find('extension:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'search'  => 'Mage_All_Latest']
        );

        self::assertContains('Package', $commandTester->getDisplay());
        self::assertContains('Version', $commandTester->getDisplay());
        self::assertContains('Mage_All_Latest', $commandTester->getDisplay());
    }
}
