<?php

namespace N98\Magento\Command\Media;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DumpCommand extends TestCase
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
