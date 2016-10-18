<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UninstallCommandTest
 * @package N98\Magento\Command\Installer
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class UninstallCommandTest extends TestCase
{
    /**
     * Check that Magento is not removed if confirmation is denied
     */
    public function testUninstallDoesNotUninstallIfConfirmationDenied()
    {
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream('no\n'));
        $command->setHelperSet(new HelperSet(array($dialog)));

        $commandTester->execute(array(
            'command'               => $command->getName(),
            '--installationFolder'  => $this->getTestMagentoRoot(),
        ));
        $this->assertEquals("Really uninstall ? [n]: ", $commandTester->getDisplay());

        //check magento still installed
        $this->assertFileExists($this->getTestMagentoRoot() . '/app/etc/local.xml');
    }

    /**
     * Check that uninstall -f actually removes magento
     */
    public function testUninstallForceActuallyRemoves()
    {
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'               => $command->getName(),
                '--force'               => true,
                '--installationFolder'  => $this->getTestMagentoRoot(),
            )
        );

        $this->assertContains("Dropped database", $commandTester->getDisplay());
        $this->assertContains("Remove directory " . $this->getTestMagentoRoot(), $commandTester->getDisplay());
        $this->assertContains("Done", $commandTester->getDisplay());
        $this->assertFileNotExists($this->getTestMagentoRoot() . '/app/etc/local.xml');
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
