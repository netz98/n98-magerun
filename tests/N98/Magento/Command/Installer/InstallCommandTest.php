<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer;

use InvalidArgumentException;
use N98\Magento\Command\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

final class InstallCommandTest extends TestCase
{
    /**
     * @var string Installation Directory
     */
    private $installDir;

    /**
     * Create temp dir for install
     */
    protected function setup(): void
    {
        $installDir = sys_get_temp_dir() . '/mageinstall';
        if (is_readable($installDir)) {
            $result = rmdir($installDir);
            if (!$result) {
                throw new RuntimeException(
                    sprintf('Failed to remove temporary install dir "%s"', $installDir),
                );
            }
        }

        $this->installDir = $installDir;
    }

    /**
     * If all database config is passed in via options and the database validation fails,
     * test that an exception was thrown
     */
    public function testInstallFailsWithInvalidDbConfigWhenAllOptionsArePassedIn()
    {
        $this->markTestIncomplete('With PHPUnit 10 the test is waiting forever. This has to be fixed.');
        $application = $this->getApplication();
        $application->add(new InstallCommand());

        $command = $this->getApplication()->find('install');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute(
                [
                    'command' => $command->getName(),
                    '--noDownload' => true,
                    '--installSampleData' => 'no',
                    '--useDefaultConfigParams' => 'yes',
                    '--installationFolder' => $this->installDir,
                    '--dbHost' => 'hostWhichDoesNotExists',
                    '--dbUser' => 'user',
                    '--dbPass' => 'pa$$w0rd',
                    '--dbName' => 'magento',
                ],
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->assertSame('Database configuration is invalid', $invalidArgumentException->getMessage());
            $display = $commandTester->getDisplay(true);
            $this->assertStringContainsString('SQLSTATE', $display);

            return;
        }

        self::fail('InvalidArgumentException was not raised');
    }

    /**
     * Remove directory made by installer
     */
    protected function tearDown(): void
    {
        if (is_readable($this->installDir)) {
            @rmdir($this->installDir);
        }
    }
}
