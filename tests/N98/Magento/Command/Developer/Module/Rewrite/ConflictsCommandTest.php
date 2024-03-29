<?php

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ConflictsCommandTest
 *
 * @TODO Check with simulated conflict
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ConflictsCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ConflictsCommand());
        $command = $this->getApplication()->find('dev:module:rewrite:conflicts');

        /**
         * Only stdout
         */
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()]
        );
        self::assertStringContainsString('No rewrite conflicts were found', $commandTester->getDisplay());

        /**
         * Junit Log without any output
         */
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(
            ['command'     => $command->getName(), '--log-junit' => '_output.xml']
        );
        self::assertEquals(0, $result);
        self::assertEquals('', $commandTester->getDisplay());
        self::assertFileExists('_output.xml');
        @unlink('_output.xml');
    }

    /**
     * Magento doesn't have any conflicts out of the box, so we need to fake one
     */
    public function testExecuteConflict()
    {
        $rewrites = ['blocks' => ['n98/mock_conflict' => ['Mage_Customer_Block_Account', 'Mage_Tag_Block_All']]];
        $command = $this->getCommandWithMockLoadRewrites($rewrites);
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['command' => $command->getName()]);
        self::assertNotEquals(0, $result);
        self::assertStringContainsString('1 conflict was found', $commandTester->getDisplay());
    }

    /**
     * This is made to look like a conflict (2 rewrites for the same class) but
     * because Bundle extends Catalog, it's valid.  Note that we're implying
     * Bundle depends on Catalog by passing it as the second value in the array.
     */
    public function testExecuteConflictFalsePositive()
    {
        $rewrites = ['blocks' => ['n98/mock_conflict' => ['Mage_Catalog_Block_Product_Price', 'Mage_Bundle_Block_Catalog_Product_Price']]];
        $command = $this->getCommandWithMockLoadRewrites($rewrites);
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['command' => $command->getName()]);
        self::assertEquals(0, $result);
        self::assertStringContainsString('No rewrite conflicts were found', $commandTester->getDisplay());
    }

    /**
     * Mock the ConflictsCommand and change the return value of loadRewrites()
     * to the given argument
     *
     * @param  array            $return
     * @return ConflictsCommand
     */
    private function getCommandWithMockLoadRewrites(array $return)
    {
        $commandMock = $this->getMockBuilder(ConflictsCommand::class)
            ->setMockClassName('ConflictsCommandMock')
            ->enableOriginalClone()
            ->onlyMethods(['loadRewrites'])
            ->getMock();
        $this->getApplication()->add($commandMock);
        $commandMock
            ->method('loadRewrites')
            ->willReturn($return);
        return $commandMock;
    }
}
