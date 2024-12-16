<?php

namespace N98\Magento\Command;

use Mage;
use Symfony\Component\Console\Tester\CommandTester;

class ScriptCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ScriptCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('script');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'filename'  => __DIR__ . '/_files/test.mr']
        );

        // Check pre defined vars
        $edition = is_callable(['\Mage', 'getEdition']) ? Mage::getEdition() : 'Community';
        self::assertStringContainsString('magento.edition: ' . $edition, $commandTester->getDisplay());

        self::assertStringContainsString('magento.root: ' . $this->getApplication()->getMagentoRootFolder(), $commandTester->getDisplay());
        self::assertStringContainsString('magento.version: ' . Mage::getVersion(), $commandTester->getDisplay());
        self::assertStringContainsString('magerun.version: ' . $this->getApplication()->getVersion(), $commandTester->getDisplay());

        self::assertStringContainsString('code', $commandTester->getDisplay());
        self::assertStringContainsString('foo.sql', $commandTester->getDisplay());
        self::assertStringContainsString('BAR: foo.sql.gz', $commandTester->getDisplay());
        self::assertStringContainsString('Magento Websites', $commandTester->getDisplay());
        self::assertStringContainsString('web/secure/base_url', $commandTester->getDisplay());
        self::assertStringContainsString('web/seo/use_rewrites => 1', $commandTester->getDisplay());
    }
}
