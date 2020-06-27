<?php

namespace N98\Magento\Command\Eav\Attribute\Create;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DummyCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $application->find('eav:attribute:create-dummy-values');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'locale'        => "en_US",
                'attribute-id'  => 92,
                'values-type'   => 'int',
                'values-number' => 1,
            )
        );

        $this->assertRegExp('/ATTRIBUTE VALUE: \'(.+)\' ADDED!/', $commandTester->getDisplay());
    }

    public function testmanageArguments()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $application->find('eav:attribute:create-dummy-values');

        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\QuestionHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        // ASK - attribute-id
        $dialog->expects($this->any())
               ->method('ask')
               ->with(
                   $this->isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Question\Question')
               )
               ->willReturn(92);

        // ASK - values-type
        $dialog->expects($this->any())
               ->method('ask')
               ->with(
                   $this->isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Question\Question')
               )
               ->willReturn('int');

        // ASK - values-number
        $dialog->expects($this->any())
               ->method('ask')
               ->with(
                   $this->isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                   $this->isInstanceOf('Symfony\Component\Console\Question\Question')
               )
               ->willReturn(1);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'                    => $command->getName(),
            )
        );

        $arguments = $commandTester->getInput()->getArguments();
        $this->assertArrayHasKey('attribute-id', $arguments);
        $this->assertArrayHasKey('values-type', $arguments);
        $this->assertArrayHasKey('values-number', $arguments);
    }
}
