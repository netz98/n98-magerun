<?php

namespace N98\Magento\Command\Cache;

use RuntimeException;
use PHPUnit\Framework\MockObject\MockObject;
use N98\Magento\Application;
use Mage;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanCommandTest extends TestCase
{
    /**
     * @throws RuntimeException
     * @return MockObject|Application
     */
    public function getApplication()
    {
        $application = parent::getApplication();

        // FIXME #613 make install command work with 1.9+ and cache initialization
        $version = Mage::getVersion();
        $against = '1.9.0.0';
        if ($application->isMagentoEnterprise()) {
            $against = '1.14.0.0';
        }
        if (-1 != version_compare($version, $against)) {
            self::markTestSkipped(
                sprintf(
                    'Test skipped because it fails after new install of a Magento 1.9+ version (Magento version is: ' .
                    '%s) which is the case on travis where we always have a new install.', $version
                )
            );
        }

        return $application;
    }

    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new CleanCommand());
        $command = $this->getApplication()->find('cache:clean');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        self::assertStringContainsString('Cache config cleaned', $commandTester->getDisplay());
    }

    public function testItCanCleanMultipleCaches()
    {
        $application = $this->getApplication();
        $application->add(new CleanCommand());
        $command = $this->getApplication()->find('cache:clean');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'type'    => ['config', 'layout']]);

        $display = $commandTester->getDisplay();

        self::assertStringContainsString('Cache config cleaned', $display);
        self::assertStringContainsString('Cache layout cleaned', $display);
    }
}
