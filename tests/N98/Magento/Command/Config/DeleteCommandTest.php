<?php

namespace N98\Magento\Command\Config;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $setCommand = $this->getApplication()->find('config:set');
        $deleteCommand = $this->getApplication()->find('config:delete');

        /**
         * Add a new entry
         */
        $commandTester = new CommandTester($setCommand);
        $commandTester->execute(
            array(
                'command' => $setCommand->getName(),
                'path'    => 'n98_magerun/foo/bar',
                'value'   => '1234',
            )
        );
        $this->assertContains('n98_magerun/foo/bar => 1234', $commandTester->getDisplay());

        $commandTester = new CommandTester($deleteCommand);
        $commandTester->execute(
            array(
                'command' => $deleteCommand->getName(),
                'path'    => 'n98_magerun/foo/bar',
            )
        );
        $this->assertContains('| n98_magerun/foo/bar | default | 0        |', $commandTester->getDisplay());

        /**
         * Delete all
         */

        foreach (\Mage::app()->getStores() as $store) {
            // add multiple entries
            $commandTester = new CommandTester($setCommand);
            $commandTester->execute(
                array(
                     'command'     => $setCommand->getName(),
                     'path'        => 'n98_magerun/foo/bar',
                     '--scope'     => 'stores',
                     '--scope-id'  => $store->getId(),
                     'value'       => 'store-' . $store->getId(),
                )
            );
        }

        $commandTester = new CommandTester($deleteCommand);
        $commandTester->execute(
            array(
                 'command' => $deleteCommand->getName(),
                 'path'    => 'n98_magerun/foo/bar',
                 '--all'   => true,
            )
        );

        foreach (\Mage::app()->getStores() as $store) {
            $this->assertContains('| n98_magerun/foo/bar | stores   | ' . $store->getId() . '        |', $commandTester->getDisplay());
        }
    }
}
