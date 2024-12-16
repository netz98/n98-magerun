<?php

namespace N98\Magento\Command\Cms\Block;

use Mage;
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
        $victim = Mage::getModel('cms/block')->getCollection()->getFirstItem();
        $commandTester->execute(
            [
                'command'  => $command->getName(),
                // id should work
                'block_id' => $victim->getId(),
            ]
        );
        self::assertStringContainsString('disabled', $commandTester->getDisplay());
        $commandTester->execute(
            [
                'command'  => $command->getName(),
                // identifier should work
                'block_id' => $victim->getIdentifier(),
            ]
        );
        self::assertStringContainsString('enabled', $commandTester->getDisplay());
    }
}
