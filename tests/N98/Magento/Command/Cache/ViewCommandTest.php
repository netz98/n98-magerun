<?php

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ViewCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:view');

        \Mage::app()->getCache()->save('TEST n98-magerun', 'n98-magerun-unittest');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'id'      => 'n98-magerun-unittest',
            )
        );

        $this->assertRegExp('/TEST n98-magerun/', $commandTester->getDisplay());
    }

    public function testExecuteUnserialize()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:view');

        $cacheData = array(
            1,
            2,
            3,
            'foo' => array('bar'),
        );
        \Mage::app()->getCache()->save(serialize($cacheData), 'n98-magerun-unittest');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'id'            => 'n98-magerun-unittest',
                '--unserialize' => true,
            )
        );

        $this->assertEquals(print_r($cacheData, true) . "\n", $commandTester->getDisplay(true));
    }
}
