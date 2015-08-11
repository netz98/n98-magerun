<?php
/**
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Magento\Command\Cache\Dir;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

/**
 * Class FlushCommandTest
 *
 * @package N98\Magento\Command\Cache
 */
class FlushCommandTest extends TestCase
{
    public function testExecute()
    {
        $command = $this->prepareCommand(new FlushCommand());
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $display = $commandTester->getDisplay();
        $this->assertContains('Flushing cache directory ', $display);
        $this->assertContains('Cache directory flushed', $display);
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
        $command = $application->find($object::NAME);

        return $command;
    }
}
