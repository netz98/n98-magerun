<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\TestCase;
use N98\Util\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $createCommand = $this->getApplication()->find('dev:module:create');
        $updateCommand = $this->getApplication()->find('dev:module:update');
        $updateCommand->setTestMode(true);
        $root = getcwd();
        $this->_deleteOldModule($root);

        $commandTester = new CommandTester($createCommand);

        $commandTester->execute(
            array(
                'command'         => $createCommand->getName(),
                '--add-all'       => true,
                '--modman'        => true,
                '--description'   => 'Unit Test Description',
                '--author-name'   => 'Unit Test',
                '--author-email'  => 'n98-magerun@example.com',
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );
        $commandTester = new CommandTester($updateCommand);

        $moduleBaseFolder = $root . '/N98Magerun_UnitTest/src/app/code/local/N98Magerun/UnitTest/';
        $dialog = $updateCommand->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream("2.0.0\n"));

        $this->_setVersionOptionTest($commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addResourceModelOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addRoutersOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addEventsOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addLayoutUpdatesOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addTranslateOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);
        $this->_addDefaultOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder);

        $this->_deleteOldModule($root);
    }

    /**
     * @param $root
     * @return bool|Filesystem
     */
    protected function _deleteOldModule($root)
    {
        // delete old module
        $filesystem = false;

        if (is_dir($root . '/N98Magerun_UnitTest')) {
            $filesystem = new Filesystem();
            $filesystem->recursiveRemoveDirectory($root . '/N98Magerun_UnitTest');
            clearstatcache();
        }
        return $filesystem;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);

        rewind($stream);
        return $stream;
    }

    /**
     * @param $moduleBaseFolder
     * @return string
     */
    protected function _getConfigXmlContents($moduleBaseFolder)
    {
        return file_get_contents($moduleBaseFolder . 'etc/config.xml');
    }

    /**
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _setVersionOptionTest($commandTester, $updateCommand, $moduleBaseFolder)
    {
        $commandTester->execute(
            array(
                'command'         => $updateCommand->getName(),
                '--set-version'   => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );

        $this->assertFileExists($moduleBaseFolder . 'etc/config.xml');

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<version>2.0.0</version>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     * @return string
     */
    protected function _addResourceModelOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("y\nentity1\nentity1table\nentity2\nentity2table\n\n"));
        $commandTester->execute(
            array(
                'command'              => $updateCommand->getName(),
                '--add-resource-model' => true,
                'vendorNamespace'      => 'N98Magerun',
                'moduleName'           => 'UnitTest',
            )
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<n98magerun_unittest_resource>', $configXmlContent);
        $this->assertContains('<deprecatedNode>n98magerun_unittest_resource_eav_mysql4</deprecatedNode>', $configXmlContent);
        $this->assertContains('<class>N98Magerun_UnitTest_Model_Resource</class>', $configXmlContent);
        $this->assertContains('<entities>', $configXmlContent);
        $this->assertContains('<entity1>', $configXmlContent);
        $this->assertContains('<table>entity1table</table>', $configXmlContent);
        $this->assertContains('<entity2>', $configXmlContent);
        $this->assertContains('<table>entity2table</table>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _addRoutersOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("admin\nstandard\nn98magerun\n"));
        $commandTester->execute(
            array(
                'command'         => $updateCommand->getName(),
                '--add-routers'   => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<admin>', $configXmlContent);
        $this->assertContains('<routers>', $configXmlContent);
        $this->assertContains('<n98magerun_unittest>', $configXmlContent);
        $this->assertContains('<args>', $configXmlContent);
        $this->assertContains('<use>standard</use>', $configXmlContent);
        $this->assertContains('<module>n98magerun_unittest</module>', $configXmlContent);
        $this->assertContains('<frontName>n98magerun</frontName>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _addEventsOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("frontend\ncontroller_action_postdispatch\nn98mageruntest_observer\nn98magerun_unittest/observer\ncontrollerActionPostdispatch"));
        $commandTester->execute(
            array(
                'command'         => $updateCommand->getName(),
                '--add-events'    => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<frontend>', $configXmlContent);
        $this->assertContains('<events>', $configXmlContent);
        $this->assertContains('<n98mageruntest_observer>', $configXmlContent);
        $this->assertContains('<class>n98magerun_unittest/observer</class>', $configXmlContent);
        $this->assertContains('<method>controllerActionPostdispatch</method>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _addLayoutUpdatesOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("adminhtml\nn98magerun_unittest\nn98magerun_unittest.xml"));
        $commandTester->execute(
            array(
                'command'              => $updateCommand->getName(),
                '--add-layout-updates' => true,
                'vendorNamespace'      => 'N98Magerun',
                'moduleName'           => 'UnitTest',
            )
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<adminhtml>', $configXmlContent);
        $this->assertContains('<layout>', $configXmlContent);
        $this->assertContains('<updates>', $configXmlContent);
        $this->assertContains('<n98magerun_unittest>', $configXmlContent);
        $this->assertContains('<file>n98magerun_unittest.xml</file>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _addTranslateOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("adminhtml\nN98magerun_UnitTest.csv"));
        $commandTester->execute(
            array(
                'command'         => $updateCommand->getName(),
                '--add-translate' => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<adminhtml>', $configXmlContent);
        $this->assertContains('<translate>', $configXmlContent);
        $this->assertContains('<modules>', $configXmlContent);
        $this->assertContains('<N98Magerun_UnitTest>', $configXmlContent);
        $this->assertContains('<files>', $configXmlContent);
        $this->assertContains('<default>N98magerun_UnitTest.csv</default>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    protected function _addDefaultOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("sectiontest\ngrouptest\nfieldname\nfieldvalue"));
        $commandTester->execute(
            array(
                'command'         => $updateCommand->getName(),
                '--add-default'   => true,
                'vendorNamespace' => 'N98Magerun',
                'moduleName'      => 'UnitTest',
            )
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertContains('<default>', $configXmlContent);
        $this->assertContains('<sectiontest>', $configXmlContent);
        $this->assertContains('<grouptest>', $configXmlContent);
        $this->assertContains('<fieldname>fieldvalue</fieldname>', $configXmlContent);
    }
}
