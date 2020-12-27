<?php

namespace N98\Magento\Command;

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
            array(
                'command'   => $command->getName(),
                'filename'  => __DIR__ . '/_files/test.mr',
            )
        );

        // Check pre defined vars
        $edition = is_callable(array('\Mage', 'getEdition')) ? \Mage::getEdition() : 'Community';
        self::assertContains('magento.edition: ' . $edition, $commandTester->getDisplay());

        self::assertContains('magento.root: ' . $this->getApplication()->getMagentoRootFolder(), $commandTester->getDisplay());
        self::assertContains('magento.version: ' . \Mage::getVersion(), $commandTester->getDisplay());
        self::assertContains('magerun.version: ' . $this->getApplication()->getVersion(), $commandTester->getDisplay());

        self::assertContains('code', $commandTester->getDisplay());
        self::assertContains('foo.sql', $commandTester->getDisplay());
        self::assertContains('BAR: foo.sql.gz', $commandTester->getDisplay());
        self::assertContains('Magento Websites', $commandTester->getDisplay());
        self::assertContains('web/secure/base_url', $commandTester->getDisplay());
        self::assertContains('web/seo/use_rewrites => 1', $commandTester->getDisplay());
    }
}
