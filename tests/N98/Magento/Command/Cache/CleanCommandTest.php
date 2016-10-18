<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanCommandTest extends TestCase
{
    /**
     * @throws \RuntimeException
     * @return \PHPUnit_Framework_MockObject_MockObject|\N98\Magento\Application
     */
    public function getApplication()
    {
        $application = parent::getApplication();

        if ($application::MAGENTO_MAJOR_VERSION_1 !== $application->getMagentoMajorVersion()) {
            return $application;
        }

        // FIXME #613 make install command work with 1.9+ and cache initialization
        $version = \Mage::getVersion();
        $against = '1.9.0.0';
        if ($application->isMagentoEnterprise()) {
            $against = '1.14.0.0';
        }
        if (-1 != version_compare($version, $against)) {
            $this->markTestSkipped(
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
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertContains('Cache config cleaned', $commandTester->getDisplay());
    }

    public function testItCanCleanMultipleCaches()
    {
        $application = $this->getApplication();
        $application->add(new CleanCommand());
        $command = $this->getApplication()->find('cache:clean');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'type'    => array('config', 'layout'),
        ));

        $display = $commandTester->getDisplay();

        $this->assertContains('Cache config cleaned', $display);
        $this->assertContains('Cache layout cleaned', $display);
    }
}
