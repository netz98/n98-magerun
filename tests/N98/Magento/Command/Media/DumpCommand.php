<?php

namespace N98\Magento\Command\Media;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class DumpCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('media:dump');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                'filename' => tempnam('media_'),
                '--strip'  => true,
            )
        );
    
        $this->assertContains('Compress directory', $commandTester->getDisplay());
    }
}