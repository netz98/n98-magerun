<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ToggleCommandTest
 *
 * @package N98\Magento\Command\Cms\Block
 */
final class ToggleCommandTest extends TestCase
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
            ],
        );
        $this->assertStringContainsString('disabled', $commandTester->getDisplay());
        $commandTester->execute(
            [
                'command'  => $command->getName(),
                // identifier should work
                'block_id' => $victim->getIdentifier(),
            ],
        );
        $this->assertStringContainsString('enabled', $commandTester->getDisplay());
    }
}
