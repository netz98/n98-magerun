<?php

namespace N98\Magento\Command\MagentoConnect;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateExtensionCommandTest extends TestCase
{
    public function testSetup()
    {
        $this->markTestSkipped('Skip Test - Currently are connect problems. We skip test.');

        $this->getApplication()->initMagento();
        if (version_compare(\Mage::getVersion(), '1.4.2.0', '<=')) {
            $this->markTestSkipped('Skip Test - mage cli script does not exist.');
        }

        $application = $this->getApplication();
        $commandMock = $this->getMockBuilder('N98\Magento\Command\MagentoConnect\ValidateExtensionCommand')
            ->setMockClassName('ValidateExtensionCommandMock')
            ->enableOriginalClone()
            ->setMethods(array('_getDownloaderConfigPath'))
            ->getMock();
        $application->add($commandMock);

        $commandMock
            ->expects($this->any())
            ->method('_getDownloaderConfigPath')
            ->will($this->returnValue(__DIR__ . '/_files/cache.cfg'));

        $commandTester = new CommandTester($commandMock);
        $commandTester->execute(
            array(
                'command'           => $commandMock->getName(),
                'package'           => 'Mage_All_Latest',
                '--include-default' => true,
            )
        );

        $output = $commandTester->getDisplay();
        $this->assertContains('Mage_All_Latest', $output);
    }
}
