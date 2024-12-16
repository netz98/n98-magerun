<?php

namespace N98\Magento\Command\Eav\Attribute\Create;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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
            ['command'       => $command->getName(), 'locale'        => 'en_US', 'attribute-id'  => 92, 'values-type'   => 'int', 'values-number' => 1]
        );

        self::assertMatchesRegularExpression('/ATTRIBUTE VALUE: \'(.+)\' ADDED!/', $commandTester->getDisplay());
    }

    public function testmanageArguments()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $application->find('eav:attribute:create-dummy-values');

        $dialog = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        // ASK - attribute-id
        $dialog
               ->method('ask')
               ->with(
                   self::isInstanceOf(InputInterface::class),
                   self::isInstanceOf(OutputInterface::class),
                   self::isInstanceOf(Question::class)
               )
               ->willReturn(92);

        // ASK - values-type
        $dialog
               ->method('ask')
               ->with(
                   self::isInstanceOf(InputInterface::class),
                   self::isInstanceOf(OutputInterface::class),
                   self::isInstanceOf(Question::class)
               )
               ->willReturn('int');

        // ASK - values-number
        $dialog
               ->method('ask')
               ->with(
                   self::isInstanceOf(InputInterface::class),
                   self::isInstanceOf(OutputInterface::class),
                   self::isInstanceOf(Question::class)
               )
               ->willReturn(1);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['command'                    => $command->getName()]
        );

        $arguments = $commandTester->getInput()->getArguments();
        self::assertArrayHasKey('attribute-id', $arguments);
        self::assertArrayHasKey('values-type', $arguments);
        self::assertArrayHasKey('values-number', $arguments);
    }
}
