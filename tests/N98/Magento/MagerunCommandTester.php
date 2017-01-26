<?php

namespace N98\Magento;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Command Tester for a Magerun Command (based on Symfony Console), for
 * use in Phpunit based integration tests
 */
class MagerunCommandTester
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var string
     */
    private $commandName;

    /**
     * @see CommandTester::execute()
     * @var array of command input
     */
    private $input;

    /**
     * @var int
     */
    private $status;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * MagerunCommandTester constructor.
     *
     * @param TestCase $testCase
     * @param string|array $input
     */
    public function __construct(TestCase $testCase, array $input)
    {
        $this->testCase = $testCase;

        $testCase->assertArrayHasKey('command', $input);
        $testCase->assertInternalType('string', $input['command']);
        $this->commandName = $input['command'];
        $this->input = $input;
    }

    /**
     * @return Command
     */
    public function getCommand()
    {
        return $this->getCommandInternal();
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        $commandTester = $this->getExecutedCommandTester();

        return $commandTester->getDisplay();
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        $this->getExecutedCommandTester();

        return $this->status;
    }

    private function getExecutedCommandTester()
    {
        $commandTester = $this->getCommandTester();
        if (!isset($this->status)) {
            $this->status = $commandTester->execute($this->input);
        }

        return $commandTester;
    }

    /**
     * @return CommandTester
     */
    private function getCommandTester()
    {
        if (isset($this->commandTester)) {
            return $this->commandTester;
        }

        $command = $this->getCommandInternal();

        $commandTester = new CommandTester($command);

        return $this->commandTester = $commandTester;
    }

    /**
     * @return Command
     */
    private function getCommandInternal()
    {
        $test = $this->testCase;

        $command = $test->getApplication()->find($this->commandName);

        $test->assertSame(
            $command->getName(),
            $this->commandName,
            'Verifying that test is done against main command name'
        );

        if (!$command instanceof Command) {
            throw new \InvalidArgumentException(
                sprintf('Command "%s" is not a console command', $this->commandName)
            );
        }

        return $command;
    }
}
