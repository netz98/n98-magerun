<?php
namespace N98\Magento\Command;
use N98\Magento\Command\PHPUnit\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateExtensionCommandTest extends TestCase
{
    public function testSetup()
    {    
        $command = $this->getApplication()->find('extension:validate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'           => $command->getName(),
                'package'           => 'Mage_All_Latest',
                '--include-default' => true
            )
        );
        
        $output = $commandTester->getDisplay();
        $this->assertContains('Mage_All_Latest', $output);
    }
}