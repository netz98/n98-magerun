<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class OnCommandTest
 *
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
class FromCommandTest extends TestCase
{
    public static function dataProviderTestExecute()
    {
        return ['Not existing module, no --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 0, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []], 'Not existing module, with --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 1, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []], 'Not existing module, with -a' => ['$moduleName'   => 'NotExistentModule', '$all'          => 2, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []], 'Mage_Admin module, no --all' => ['$moduleName'   => 'Mage_Admin', '$all'          => 0, '$expectations' => ['Mage_Adminhtml'], '$notContains'  => ['Mage_AdminNotification']], 'Mage_Admin module, with --all' => ['$moduleName'   => 'Mage_Admin', '$all'          => 1, '$expectations' => ['Mage_AdminNotification', 'Mage_Adminhtml'], '$notContains'  => ['Mage_Compiler', 'Mage_Customer']], 'Mage_Admin module, with -a' => ['$moduleName'   => 'Mage_Admin', '$all'          => 2, '$expectations' => ['Mage_AdminNotification', 'Mage_Adminhtml'], '$notContains'  => ['Mage_Compiler', 'Mage_Customer']]];
    }

    /**
     * @dataProvider dataProviderTestExecute
     * @param string $moduleName
     * @param int $all
     * @param array $contains
     * @param array $notContains
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
        foreach ($contains as $expectation) {
            self::assertStringContainsString($expectation, $commandTester->getDisplay());
        }
        foreach ($notContains as $expectation) {
            self::assertStringNotContainsString($expectation, $commandTester->getDisplay());
        }
    }
}
