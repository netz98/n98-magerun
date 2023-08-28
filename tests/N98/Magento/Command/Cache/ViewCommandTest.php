<?php

namespace N98\Magento\Command\Cache;

use Mage;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ViewCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:view');

        Mage::app()->getCache()->save('TEST n98-magerun', 'n98-magerun-unittest');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'id'      => 'n98-magerun-unittest']
        );

        self::assertMatchesRegularExpression('/TEST n98-magerun/', $commandTester->getDisplay());
    }

    public function testExecuteUnserialize()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:view');

        $cacheData = [1, 2, 3, 'foo' => ['bar']];
        Mage::app()->getCache()->save(serialize($cacheData), 'n98-magerun-unittest');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'id'            => 'n98-magerun-unittest', '--unserialize' => true]
        );

        self::assertEquals(print_r($cacheData, true) . "\n", $commandTester->getDisplay(true));
    }
}
