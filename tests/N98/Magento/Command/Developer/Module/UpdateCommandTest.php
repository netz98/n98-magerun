<?php

namespace N98\Magento\Command\Developer\Module;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;
use N98\Util\Filesystem;

class UpdateCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $createCommand = $this->getApplication()->find('dev:module:create');
        $updateCommand = $this->getApplication()->find('dev:module:update');

        $root = getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');

        if (!$root)
            $root = getcwd();

        $this->_deleteOldModule($root);

        $commandTester = new CommandTester($createCommand);

        $commandTester->execute(
            array(
                'command'   => $createCommand->getName(),
                '--add-all'       => true,
                '--description'   => 'Unit Test Description',
                '--author-name'   => 'Unit Test',
                '--author-email'  => 'n98-magerun@example.com',
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest'
            )
        );
        $commandTester = new CommandTester($updateCommand);

        $dialog = $updateCommand->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream("2.0.0"));

        $commandTester->execute(
            array(
                'command'   => $updateCommand->getName(),
                '--set-version'   => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest'
            )
        );

        $moduleBaseFolder = $root . '/app/code/local/N98Magerun/UnitTest/';
        $this->assertFileExists($moduleBaseFolder . 'etc/config.xml');
        $configXmlContent = file_get_contents($moduleBaseFolder . 'etc/config.xml');
        $this->assertContains('<version>2.0.0</version>', $configXmlContent);

        $this->_deleteOldModule($root);
    }

    /**
     * @param $root
     * @return bool|Filesystem
     */
    protected function _deleteOldModule($root)
    {
        // delete old module
        $filesystem = false;
        if (is_dir($root . '/app/code/local/N98Magerun')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/app/code/local/N98Magerun');
            clearstatcache();
        }
        // delete old module
        if (is_dir($root . '/N98Magerun_UnitTest')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/N98Magerun_UnitTest');
            clearstatcache();
        }
        return $filesystem;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);

        rewind($stream);
        return $stream;
    }

}