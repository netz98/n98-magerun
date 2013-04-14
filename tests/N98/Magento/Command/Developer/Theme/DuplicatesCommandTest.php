<?php

namespace N98\Magento\Command\Developer\Theme;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DuplicatesCommand());
        $command = $this->getApplication()->find('dev:theme:duplicates');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'theme'         => 'base/default',
                'originalTheme' => 'base/default',
            )
        );
    
        $this->assertContains('template/catalog/product/price.phtml', $commandTester->getDisplay());
        $this->assertContains('layout/catalog.xml', $commandTester->getDisplay());
        $this->assertNotContains('No duplicates was found', $commandTester->getDisplay());
    }
}