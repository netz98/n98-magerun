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
            'Mage_Compiler module, no --all' => array(
                '$moduleName'   => 'Mage_Compiler',
                '$all'          => 0,
                '$expectations' => array("No modules depend on Mage_Compiler module"),
                '$notContains'  => array(),
            ),
            'Mage_Compiler module, with --all' => array(
                '$moduleName'   => 'Mage_Compiler',
                '$all'          => 1,
                '$expectations' => array("No modules depend on Mage_Compiler module"),
                '$notContains'  => array(),
            ),
            'Mage_Compiler module, with -a' => array(
                '$moduleName'   => 'Mage_Compiler',
                '$all'          => 2,
                '$expectations' => array("No modules depend on Mage_Compiler module"),
                '$notContains'  => array(),
            ),
            'Mage_Admin module, no --all' => array(
                '$moduleName'   => 'Mage_Admin',
                '$all'          => 0,
                '$expectations' => array('Mage_Adminhtml'),
                '$notContains'  => array('Mage_AdminNotification'),
            ),
            'Mage_Admin module, with --all' => array(
                '$moduleName'   => 'Mage_Admin',
                '$all'          => 1,
                '$expectations' => array('Mage_AdminNotification', 'Mage_Adminhtml'/*, 'Mage_Captcha', 'Mage_Persistent'*/),
                '$notContains'  => array('Mage_Compiler', 'Mage_Customer'),
            ),
            'Mage_Admin module, with -a' => array(
                '$moduleName'   => 'Mage_Admin',
                '$all'          => 2,
                '$expectations' => array('Mage_AdminNotification', 'Mage_Adminhtml'/*, 'Mage_Captcha', 'Mage_Persistent'*/),
                '$notContains'  => array('Mage_Compiler', 'Mage_Customer'),
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
        $application->add(new FromCommand());
        $command = $this->getApplication()->find('dev:module:dependencies:from');

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
