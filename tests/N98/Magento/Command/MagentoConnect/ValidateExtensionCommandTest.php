<?php

namespace N98\Magento\Command\MagentoConnect;
use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateExtensionCommandTest extends TestCase
{
    public function testSetup()
    {
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
                '--include-default' => true
            )
        );
        
        $output = $commandTester->getDisplay();
        $this->assertContains('Mage_All_Latest', $output);
    }
}
