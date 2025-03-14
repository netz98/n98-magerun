<?php

declare(strict_types=1);

namespace N98\Magento\Command\Installer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UninstallCommandTest
 * @package N98\Magento\Command\Installer
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
final class UninstallCommandTest extends TestCase
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

        $questionHelper = new QuestionHelper();
        $questionHelper->setInputStream($this->getInputStream('no\n'));

        $command->setHelperSet(new HelperSet([$questionHelper]));

        $commandTester->execute(['command'               => $command->getName(), '--installationFolder'  => $this->getTestMagentoRoot()]);
        $this->assertSame('Really uninstall ? [n]: ', $commandTester->getDisplay());

        //check magento still installed
        $this->assertFileExists($this->getTestMagentoRoot() . '/app/etc/local.xml');
    }

    /**
     * Check that uninstall -f actually removes magento
     */
    public function testUninstallForceActuallyRemoves()
    {
        $this->markTestIncomplete('Find a replacement for setInputStream() of old DialogHelper');
        $application = $this->getApplication();
        $application->add(new UninstallCommand());

        $command = $this->getApplication()->find('uninstall');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['command'               => $command->getName(), '--force'               => true, '--installationFolder'  => $this->getTestMagentoRoot()],
        );

        $this->assertStringContainsString('Dropped database', $commandTester->getDisplay());
        $this->assertStringContainsString('Remove directory ' . $this->getTestMagentoRoot(), $commandTester->getDisplay());
        $this->assertStringContainsString('Done', $commandTester->getDisplay());
        $this->assertFileDoesNotExist($this->getTestMagentoRoot() . '/app/etc/local.xml');
    }

    /**
     * @param $input
     * @return resource
     */
    private function getInputStream($input)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $input);
        rewind($stream);
        return $stream;
    }
}
