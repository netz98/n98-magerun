<?php

declare(strict_types=1);

namespace N98\Magento\Command;

use Mage;
use Symfony\Component\Console\Tester\CommandTester;

final class ScriptCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ScriptCommand());
        $application->setAutoExit(false);

        $command = $this->getApplication()->find('script');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'filename'  => __DIR__ . '/_files/test.mr'],
        );

        // Check pre defined vars
        $edition = is_callable(['\Mage', 'getEdition']) ? Mage::getEdition() : 'Community';
        $this->assertStringContainsString('magento.edition: ' . $edition, $commandTester->getDisplay());

        $this->assertStringContainsString('magento.root: ' . $this->getApplication()->getMagentoRootFolder(), $commandTester->getDisplay());
        $this->assertStringContainsString('magento.version: ' . Mage::getVersion(), $commandTester->getDisplay());
        $this->assertStringContainsString('magerun.version: ' . $this->getApplication()->getVersion(), $commandTester->getDisplay());

        $this->assertStringContainsString('code', $commandTester->getDisplay());
        $this->assertStringContainsString('foo.sql', $commandTester->getDisplay());
        $this->assertStringContainsString('BAR: foo.sql.gz', $commandTester->getDisplay());
        $this->assertStringContainsString('Magento Websites', $commandTester->getDisplay());
        $this->assertStringContainsString('web/secure/base_url', $commandTester->getDisplay());
        $this->assertStringContainsString('web/seo/use_rewrites => 1', $commandTester->getDisplay());
    }
}
