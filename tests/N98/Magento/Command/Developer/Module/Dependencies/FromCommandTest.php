<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Dependencies;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class OnCommandTest
 *
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
final class FromCommandTest extends TestCase
{
    /**
     * @return \Iterator<array<array<string, mixed>, mixed>>
     */
    public static function dataProviderTestExecute(): \Iterator
    {
        yield 'Not existing module, no --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 0, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Not existing module, with --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 1, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Not existing module, with -a' => ['$moduleName'   => 'NotExistentModule', '$all'          => 2, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Mage_Admin module, no --all' => ['$moduleName'   => 'Mage_Admin', '$all'          => 0, '$expectations' => ['Mage_Adminhtml'], '$notContains'  => ['Mage_AdminNotification']];
        yield 'Mage_Admin module, with --all' => ['$moduleName'   => 'Mage_Admin', '$all'          => 1, '$expectations' => ['Mage_AdminNotification', 'Mage_Adminhtml'], '$notContains'  => ['Mage_Compiler', 'Mage_Customer']];
        yield 'Mage_Admin module, with -a' => ['$moduleName'   => 'Mage_Admin', '$all'          => 2, '$expectations' => ['Mage_AdminNotification', 'Mage_Adminhtml'], '$notContains'  => ['Mage_Compiler', 'Mage_Customer']];
    }

    /**
     * @dataProvider dataProviderTestExecute
     * @param string $moduleName
     * @param int $all
     * @param string[] $contains
     * @param string[] $notContains
     */
    public function testExecute($moduleName, $all, array $contains, array $notContains)
    {
        $application = $this->getApplication();
        $application->add(new FromCommand());

        $command = $this->getApplication()->find('dev:module:dependencies:from');

        $commandTester = new CommandTester($command);
        $input = ['command' => $command->getName(), 'moduleName' => $moduleName];

        switch ($all) {
            case 2:
                $input['-a'] = true;
                break;
            case 1:
                $input['--all'] = true;
                break;
            default:
                break;
        }

        $commandTester->execute($input);
        foreach ($contains as $contain) {
            $this->assertStringContainsString($contain, $commandTester->getDisplay());
        }

        foreach ($notContains as $notContain) {
            $this->assertStringNotContainsString($notContain, $commandTester->getDisplay());
        }
    }
}
