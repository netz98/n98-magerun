<?php

namespace N98\Magento\Command\Installer;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

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
        $this->installDir = sys_get_temp_dir() . "/mageinstall";
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
                    'command'                   => $command->getName(),
                    '--noDownload'              => true,
                    '--installSampleData'       => 'no',
                    '--useDefaultConfigParams'  => 'yes',
                    '--installationFolder'      => $this->installDir,
                    '--dbHost'                  => 'hostWhichDoesntExist',
                    '--dbUser'                  => 'user',
                    '--dbPass'                  => 'pa$$w0rd',
                    '--dbName'                  => 'magento',
                )
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('SQLSTATE', $commandTester->getDisplay());
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