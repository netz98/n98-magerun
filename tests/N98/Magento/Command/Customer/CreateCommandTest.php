<?php

declare(strict_types=1);

namespace N98\Magento\Command\Customer;

use Mage;
use N98\Util\Console\Helper\ParameterHelper;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateCommandTest extends TestCase
{
    /**
     * @outputBuffering
     */
    public function testExecute()
    {
        $createCommand = $this->_getCommand();
        $generatedEmail = uniqid('', true) . '@example.com';

        $this->getApplication()->initMagento();

        $website = Mage::app()->getWebsite();

        $commandTester = new CommandTester($createCommand);
        $options = ['command'   => $createCommand->getName(), 'email'     => $generatedEmail, 'password'  => 'password123', 'firstname' => 'John', 'lastname'  => 'Doe', 'website'   => $website->getCode()];
        $commandTester->execute($options);
        $this->assertMatchesRegularExpression('/Customer ' . $generatedEmail . ' successfully created/', $commandTester->getDisplay());

        // Format option
        $commandTester = new CommandTester($createCommand);
        $generatedEmail = uniqid('', true) . '@example.com';
        $options['email'] = $generatedEmail;
        $options['--format'] = 'csv';
        $this->assertSame(0, $commandTester->execute($options));
        $this->assertStringContainsString('email,password,firstname,lastname', $commandTester->getDisplay());
        $this->assertStringContainsString($generatedEmail . ',password123,John,Doe', $commandTester->getDisplay());
    }

    public function testWithWrongPassword()
    {
        $dialog = null;
        $command = null;
        $commandTester = null;
        $options = null;
        self::markTestIncomplete('We currently cannot deal with interactive commands');

        $command = $this->_getCommand();
        $generatedEmail = uniqid('', true) . '@example.com';

        // mock dialog
        // We mock the DialogHelper
        $dialog = $this->createMock(ParameterHelper::class);
        $dialog->expects(self::at(0))
            ->method('askPassword')
            ->willReturn(true); // The user confirms

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'parameter');

        $options = ['command'   => $command->getName(), 'email'     => $generatedEmail, 'password'  => 'pass', 'firstname' => 'John', 'lastname'  => 'Doe'];
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
        $this->assertMatchesRegularExpression('/The password must have at least 6 characters. Leading or trailing spaces will be ignored./', $commandTester->getDisplay());
    }

    /**
     * @return CreateCommand
     */
    private function _getCommand()
    {
        $application = $this->getApplication();
        $application->add(new CreateCommand());

        // try to create a customer with a password < 6 chars
        $command = $this->getApplication()->find('customer:create');

        return $command;
    }
}
