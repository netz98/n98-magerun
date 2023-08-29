<?php

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
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
        $this->markTestIncomplete('Find a replacement for setInputStream() of old DialogHelper');
        $application = $this->getApplication();
        $application->add(new UninstallCommand());
        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $dialog = new QuestionHelper();
        $dialog->setInputStream($this->getInputStream('no\n'));
        $command->setHelperSet(new HelperSet([$dialog]));

        $commandTester->execute(['command'               => $command->getName(), '--installationFolder'  => $this->getTestMagentoRoot()]);
        self::assertEquals("Really uninstall ? [n]: ", $commandTester->getDisplay());

        //check magento still installed
        self::assertFileExists($this->getTestMagentoRoot() . '/app/etc/local.xml');
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
            ['command'               => $command->getName(), '--force'               => true, '--installationFolder'  => $this->getTestMagentoRoot()]
        );

        self::assertStringContainsString("Dropped database", $commandTester->getDisplay());
        self::assertStringContainsString("Remove directory " . $this->getTestMagentoRoot(), $commandTester->getDisplay());
        self::assertStringContainsString("Done", $commandTester->getDisplay());
        self::assertFileDoesNotExist($this->getTestMagentoRoot() . '/app/etc/local.xml');
    }

    /**
     * @param $input
     * @return resource
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fputs($stream, $input);
        rewind($stream);
        return $stream;
    }
}
