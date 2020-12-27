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

        self::assertFileExists($root . '/N98Magerun_UnitTest/composer.json');
        self::assertFileExists($root . '/N98Magerun_UnitTest/readme.md');
        $moduleBaseFolder = $root . '/N98Magerun_UnitTest/src/app/code/local/N98Magerun/UnitTest/';
        self::assertFileExists($moduleBaseFolder . 'etc/config.xml');
        self::assertFileExists($moduleBaseFolder . 'controllers');
        self::assertFileExists($moduleBaseFolder . 'Block');
        self::assertFileExists($moduleBaseFolder . 'Model');
        self::assertFileExists($moduleBaseFolder . 'Helper');
        self::assertFileExists($moduleBaseFolder . 'data/n98magerun_unittest_setup');
        self::assertFileExists($moduleBaseFolder . 'sql/n98magerun_unittest_setup');

        // delete old module
        if (is_dir($root . '/N98Magerun_UnitTest')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/N98Magerun_UnitTest');
            clearstatcache();
        }
    }
}
