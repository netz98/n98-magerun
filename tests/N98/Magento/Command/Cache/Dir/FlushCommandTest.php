<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Cache\Dir;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class FlushCommandTest
 *
 * @package N98\Magento\Command\Cache
 */
final class FlushCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->prepareCommand(new FlushCommand());
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Flushing cache directory ', $display);
        $this->assertStringContainsString('Cache directory flushed', $display);
    }

    /**
     * @param $object
     *
     * @return Command
     */
    private function prepareCommand($object)
    {
        $application = $this->getApplication();
        $application->add($object);

        return $application->find($object::NAME);
    }
}
