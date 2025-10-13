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
final class OnCommandTest extends TestCase
{
    /**
     * @return \Iterator<array<array<string, mixed>, mixed>>
     */
    public static function dataProviderTestExecute(): \Iterator
    {
        yield 'Not existing module, no --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 0, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Not existing module, with --all' => ['$moduleName'   => 'NotExistentModule', '$all'          => 1, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Not existing module, with -a' => ['$moduleName'   => 'NotExistentModule', '$all'          => 2, '$expectations' => ['Module NotExistentModule was not found'], '$notContains'  => []];
        yield 'Mage_Core module, no --all' => ['$moduleName'   => 'Mage_Core', '$all'          => 0, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []];
        yield 'Mage_Core module, with --all' => ['$moduleName'   => 'Mage_Core', '$all'          => 1, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []];
        yield 'Mage_Core module, with -a' => ['$moduleName'   => 'Mage_Core', '$all'          => 2, '$expectations' => ["Module Mage_Core doesn't have dependencies"], '$notContains'  => []];
        yield 'Mage_Customer module, no --all' => ['$moduleName'   => 'Mage_Customer', '$all'          => 0, '$expectations' => [
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => ['Mage_Core']];
        yield 'Mage_Customer module, with --all' => ['$moduleName'   => 'Mage_Customer', '$all'          => 1, '$expectations' => [
            'Mage_Core',
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => []];
        yield 'Mage_Customer module, with -a' => ['$moduleName'   => 'Mage_Customer', '$all'          => 2, '$expectations' => [
            'Mage_Core',
            'Mage_Dataflow',
            /*'Mage_Directory',*/
            'Mage_Eav',
        ], '$notContains'  => []];
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
        foreach ($contains as $contain) {
            $this->assertStringContainsString($contain, $commandTester->getDisplay());
        }

        foreach ($notContains as $notContain) {
            $this->assertStringNotContainsString($notContain, $commandTester->getDisplay());
        }
    }
}
