<?php

namespace N98\Magento\Command\Developer\Module\Dependencies;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class OnCommandTest
 *
 * @package N98\Magento\Command\Developer\Module\Dependencies
 */
class OnCommandTest extends TestCase
{
    public static function dataProviderTestExecute()
    {
        return ['Not existing module, no --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 0, '$expectations' => ["Module NotExistentModule was not found"], '$notContains'  => []], 'Not existing module, with --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 1, '$expectations' => ["Module NotExistentModule was not found"], '$notContains'  => []], 'Not existing module, with -a' => ['$moduleName'   => 'NotExistentModule', '$all'          => 2, '$expectations' => ["Module NotExistentModule was not found"], '$notContains'  => []], 'Mage_Core module, no --all' => ['$moduleName'   => 'Mage_Core', '$all'          => 0, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []], 'Mage_Core module, with --all' => ['$moduleName'   => 'Mage_Core', '$all'          => 1, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []], 'Mage_Core module, with -a' => ['$moduleName'   => 'Mage_Core', '$all'          => 2, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []], 'Mage_Customer module, no --all' => ['$moduleName'   => 'Mage_Customer', '$all'          => 0, '$expectations' => [
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => ['Mage_Core']], 'Mage_Customer module, with --all' => ['$moduleName'   => 'Mage_Customer', '$all'          => 1, '$expectations' => [
            'Mage_Core',
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => []], 'Mage_Customer module, with -a' => ['$moduleName'   => 'Mage_Customer', '$all'          => 2, '$expectations' => [
            'Mage_Core',
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => []]];
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
        $application->add(new OnCommand());
        $command = $this->getApplication()->find('dev:module:dependencies:on');

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
