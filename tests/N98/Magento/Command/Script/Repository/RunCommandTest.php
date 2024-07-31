<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();

        $testDir = $this->normalizePathSeparators(__DIR__) . '/_scripts';

        $config['script']['folders'][] = $testDir;
        $application->setConfig($config);

        $application->add(new RunCommand());
        $command = $this->getApplication()->find('script:repo:run');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'script'  => 'hello-world']
        );

        // Runs sys:info -> Check for any output
        self::assertStringContainsString('Vendors (core)', $commandTester->getDisplay());

        self::assertStringContainsString(
            $testDir . '/hello-world.magerun',
            $this->normalizePathSeparators($commandTester->getDisplay())
        );
    }

    /**
     * @return string
     */
    private function normalizePathSeparators($string)
    {
        return strtr($string, '\\', '/');
    }
}
