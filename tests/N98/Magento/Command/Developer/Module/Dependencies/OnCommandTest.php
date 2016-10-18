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
        return array(
            'Not existing module, no --all' => array(
                '$moduleName'   => 'NotExistentModule',
                '$all'          => 0,
                '$expectations' => array("Module NotExistentModule was not found"),
                '$notContains'  => array(),
            ),
            'Not existing module, with --all' => array(
                '$moduleName'   => 'NotExistentModule',
                '$all'          => 1,
                '$expectations' => array("Module NotExistentModule was not found"),
                '$notContains'  => array(),
            ),
            'Not existing module, with -a' => array(
                '$moduleName'   => 'NotExistentModule',
                '$all'          => 2,
                '$expectations' => array("Module NotExistentModule was not found"),
                '$notContains'  => array(),
            ),
            'Mage_Core module, no --all' => array(
                '$moduleName'   => 'Mage_Core',
                '$all'          => 0,
                '$expectations' => array("Module Mage_Core doesn't have dependencies"),
                '$notContains'  => array(),
            ),
            'Mage_Core module, with --all' => array(
                '$moduleName'   => 'Mage_Core',
                '$all'          => 1,
                '$expectations' => array("Module Mage_Core doesn't have dependencies"),
                '$notContains'  => array(),
            ),
            'Mage_Core module, with -a' => array(
                '$moduleName'   => 'Mage_Core',
                '$all'          => 2,
                '$expectations' => array("Module Mage_Core doesn't have dependencies"),
                '$notContains'  => array(),
            ),
            'Mage_Customer module, no --all' => array(
                '$moduleName'   => 'Mage_Customer',
                '$all'          => 0,
                '$expectations' => array('Mage_Dataflow', /*'Mage_Directory',*/ 'Mage_Eav'),
                '$notContains'  => array('Mage_Core'),
            ),
            'Mage_Customer module, with --all' => array(
                '$moduleName'   => 'Mage_Customer',
                '$all'          => 1,
                '$expectations' => array('Mage_Core', 'Mage_Dataflow', /*'Mage_Directory',*/ 'Mage_Eav'),
                '$notContains'  => array(),
            ),
            'Mage_Customer module, with -a' => array(
                '$moduleName'   => 'Mage_Customer',
                '$all'          => 2,
                '$expectations' => array('Mage_Core', 'Mage_Dataflow', /*'Mage_Directory',*/ 'Mage_Eav'),
                '$notContains'  => array(),
            ),
        );
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
        $input = array(
            'command' => $command->getName(), 'moduleName' => $moduleName,
        );

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
            $this->assertContains($expectation, $commandTester->getDisplay());
        }
        foreach ($notContains as $expectation) {
            $this->assertNotContains($expectation, $commandTester->getDisplay());
        }
    }
}
