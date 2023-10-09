<?php

namespace N98\Magento\Command\Admin\User;

use Symfony\Component\Console\Helper\DialogHelper;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UnlockUserCommandTest
 */
class UnlockUserCommandTest extends TestCase
{
    private function getCommand()
    {
        $command = new UnlockCommand();
        $command->setApplication($this->getApplication());

        return $command;
    }

    public function testUnlockAllUsersPromptNo()
    {
        $questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelper->expects(self::once())
            ->method('ask')
            ->willReturn('n');

        $application = $this->getApplication();
        $application->add($this->getCommand());
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertStringNotContainsString('All admins unlocked', $commandTester->getDisplay());
    }

    public function testUnlockAllUsersPromptYes()
    {
        $questionHelperMock = $this->getMockBuilder(QuestionHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['ask'])
            ->getMock();

        $questionHelperMock->expects(self::once())
            ->method('ask')
            ->willReturn('y');

        $application = $this->getApplication();
        $application->add($this->getCommand());
        $command = $this->getApplication()->find('admin:user:unlock');
        $command->getHelperSet()->set($questionHelperMock, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertStringContainsString('All admins unlocked', $commandTester->getDisplay());
    }
}
