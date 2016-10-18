<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class InstallCommandTest extends TestCase
{
    /**
     * @var string Installation Directory
     */
    protected $installDir;

    /**
     * Create temp dir for install
     */
    public function setup()
    {
        $installDir = sys_get_temp_dir() . "/mageinstall";
        if (is_readable($installDir)) {
            $result = rmdir($installDir);
            if (!$result) {
                throw new RuntimeException(
                    sprintf('Failed to remove temporary install dir %s', var_export($installDir))
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
        $application = $this->getApplication();
        $application->add(new InstallCommand());
        $command = $this->getApplication()->find('install');
        $command->setCliArguments(
            array(
                '--dbName=magento',
                '--dbHost=hostWhichDoesntExist',
                '--dbUser=user',
                '--dbPass=pa$$w0rd',
            )
        );

        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute(
                array(
                    'command'                  => $command->getName(),
                    '--noDownload'             => true,
                    '--installSampleData'      => 'no',
                    '--useDefaultConfigParams' => 'yes',
                    '--installationFolder'     => $this->installDir,
                    '--dbHost'                 => 'hostWhichDoesNotExists',
                    '--dbUser'                 => 'user',
                    '--dbPass'                 => 'pa$$w0rd',
                    '--dbName'                 => 'magento',
                )
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("Database configuration is invalid", $e->getMessage());
            $display = $commandTester->getDisplay(true);
            $this->assertContains('SQLSTATE', $display);

            return;
        }

        $this->fail('InvalidArgumentException was not raised');
    }

    /**
     * Remove directory made by installer
     */
    public function tearDown()
    {
        if (is_readable($this->installDir)) {
            @rmdir($this->installDir);
        }
    }
}
