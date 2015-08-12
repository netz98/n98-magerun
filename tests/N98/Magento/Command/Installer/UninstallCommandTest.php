<?php

namespace N98\Magento\Command\Installer;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use N98\Magento\Command\PHPUnit\TestCase;
use org\bovigo\vfs\vfsStream;

class UninstallCommandTest extends TestCase
{
    /**
     * @return string Get Magento Root
     */
    protected function getMageRoot()
    {
        return getenv('N98_MAGERUN_TEST_MAGENTO_ROOT');
    }

    /**
     * @return string Get Magento local.xml file location
     */
    protected function getMagentoFile()
    {
        return getenv('N98_MAGERUN_TEST_MAGENTO_ROOT') . "/app/etc/local.xml";
    }


    /**
     * Check that uninstall -f actually removes magento
     */
    public function testUninstallForceActuallyRemoves()
    {
        /*
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command' => $command->getName(),
                '--force' => true
            )
        );

        $this->assertContains("Dropped database", $commandTester->getDisplay());
        $this->assertContains("Remove directory " . $this->getMageRoot(), $commandTester->getDisplay());
        $this->assertContains("Done", $commandTester->getDisplay());
        $this->assertFileNotExists($this->getMagentoFile());
        */
    }

    /**
     * Check that Magento is not removed if confirmation is denied
     */
    public function testUninstallDoesNotUninstallIfConfirmationDenied()
    {
        /*
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream('no\n'));
        $command->setHelperSet(new HelperSet(array($dialog)));

        $commandTester->execute(array('command' => $command->getName()));
        $this->assertEquals("Really uninstall ? [n]: ", $commandTester->getDisplay());

        //check magento still installed
        $this->assertFileExists($this->getMagentoFile());
        */
    }

    /**
     * Check that Magento is removed if confirmation is supplied
     */
    public function testUninstallSucceedsWithConfirmation()
    {
        /*
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream('yes\n'));
        $command->setHelperSet(new HelperSet(array($dialog)));

        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains("Really uninstall ? [n]: ", $commandTester->getDisplay());
        $this->assertContains("Dropped database", $commandTester->getDisplay());
        $this->assertContains("Remove directory " . $this->getMageRoot(), $commandTester->getDisplay());
        $this->assertContains("Done", $commandTester->getDisplay());
        $this->assertFileNotExists($this->getMagentoFile());
        */
    }

    /**
     * @param $input
     * @return resource
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);
        return $stream;
    }
}