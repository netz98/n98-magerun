<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\TestCase;
use N98\Util\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('dev:module:create');

        $root = getcwd();

        // delete old module
        if (is_dir($root . '/N98Magerun_UnitTest')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/N98Magerun_UnitTest');
            clearstatcache();
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'         => $command->getName(),
                '--add-all'       => true,
                '--add-setup'     => true,
                '--add-readme'    => true,
                '--add-composer'  => true,
                '--modman'        => true,
                '--description'   => 'Unit Test Description',
                '--author-name'   => 'Unit Test',
                '--author-email'  => 'n98-magerun@example.com',
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );

        $this->assertFileExists($root . '/N98Magerun_UnitTest/composer.json');
        $this->assertFileExists($root . '/N98Magerun_UnitTest/readme.md');
        $moduleBaseFolder = $root . '/N98Magerun_UnitTest/src/app/code/local/N98Magerun/UnitTest/';
        $this->assertFileExists($moduleBaseFolder . 'etc/config.xml');
        $this->assertFileExists($moduleBaseFolder . 'controllers');
        $this->assertFileExists($moduleBaseFolder . 'Block');
        $this->assertFileExists($moduleBaseFolder . 'Model');
        $this->assertFileExists($moduleBaseFolder . 'Helper');
        $this->assertFileExists($moduleBaseFolder . 'data/n98magerun_unittest_setup');
        $this->assertFileExists($moduleBaseFolder . 'sql/n98magerun_unittest_setup');

        // delete old module
        if (is_dir($root . '/N98Magerun_UnitTest')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/N98Magerun_UnitTest');
            clearstatcache();
        }
    }
}
