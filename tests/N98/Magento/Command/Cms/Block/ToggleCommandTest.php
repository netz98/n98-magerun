<?php

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ToggleCommandTest
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ToggleCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ToggleCommand());
        $command = $this->getApplication()->find('cms:block:toggle');
        $commandTester = new CommandTester($command);
        $victim = \Mage::getModel('cms/block')->getCollection()->getFirstItem();
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                // id should work
                'block_id' => $victim->getId(),
            )
        );
        $this->assertContains('disabled', $commandTester->getDisplay());
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                // identifier should work
                'block_id' => $victim->getIdentifier(),
            )
        );
        $this->assertContains('enabled', $commandTester->getDisplay());
    }
}
