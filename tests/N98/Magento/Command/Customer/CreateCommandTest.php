<?php

namespace N98\Magento\Command\Customer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    /**
     * @outputBuffering
     */
    public function testExecute()
    {
        $command = $this->_getCommand();
        $generatedEmail = uniqid() . '@example.com';

        $this->getApplication()->initMagento();

        $website = \Mage::app()->getWebsite();

        $commandTester = new CommandTester($command);
        $options = array(
            'command'   => $command->getName(),
            'email'     => $generatedEmail,
            'password'  => 'password123',
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'website'   => $website->getCode(),
        );
        $commandTester->execute($options);
        self::assertRegExp('/Customer ' . $generatedEmail . ' successfully created/', $commandTester->getDisplay());

        // Format option
        $commandTester = new CommandTester($command);
        $generatedEmail = uniqid() . '@example.com';
        $options['email'] = $generatedEmail;
        $options['--format'] = 'csv';
        self::assertEquals(0, $commandTester->execute($options));
        self::assertContains('email,password,firstname,lastname', $commandTester->getDisplay());
        self::assertContains($generatedEmail . ',password123,John,Doe', $commandTester->getDisplay());
    }

    public function testWithWrongPassword()
    {
        self::markTestIncomplete('We currently cannot deal with interactive commands');

        $command = $this->_getCommand();
        $generatedEmail = uniqid() . '@example.com';

        // mock dialog
        // We mock the DialogHelper
        $dialog = $this->createMock('N98\Util\Console\Helper\ParameterHelper');
        $dialog->expects(self::at(0))
            ->method('askPassword')
            ->will(self::returnValue(true)); // The user confirms

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'parameter');

        $options = array(
            'command'   => $command->getName(),
            'email'     => $generatedEmail,
            'password'  => 'pass',
            'firstname' => 'John',
            'lastname'  => 'Doe',
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
        self::assertRegExp('/The password must have at least 6 characters. Leading or trailing spaces will be ignored./', $commandTester->getDisplay());
    }

    /**
     * @return CreateCommand
     */
    protected function _getCommand()
    {
        $application = $this->getApplication();
        $application->add(new CreateCommand());

        // try to create a customer with a password < 6 chars
        $command = $this->getApplication()->find('customer:create');

        return $command;
    }
}
