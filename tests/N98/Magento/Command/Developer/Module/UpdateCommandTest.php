<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\TestCase;
use N98\Util\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCommandTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testExecute()
    {
        $this->markTestIncomplete('Find a replacement for missing setInputStream of question helper');
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $createCommand = $this->getApplication()->find('dev:module:create');
        $updateCommand = $this->getApplication()->find('dev:module:update');
        $updateCommand->setTestMode(true);
        $root = getcwd();
        $this->_deleteOldModule($root);

        $commandTester = new CommandTester($createCommand);

        $commandTester->execute(
            ['command'         => $createCommand->getName(), '--add-all'       => true, '--modman'        => true, '--description'   => 'Unit Test Description', '--author-name'   => 'Unit Test', '--author-email'  => 'n98-magerun@example.com', 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
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
        $stream = fopen('php://memory', 'rb+', false);
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
            ['command'         => $updateCommand->getName(), '--set-version'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
        );

        self::assertFileExists($moduleBaseFolder . 'etc/config.xml');

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<version>2.0.0</version>', $configXmlContent);
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
            ['command'              => $updateCommand->getName(), '--add-resource-model' => true, 'vendorNamespace'      => 'N98Magerun', 'moduleName'           => 'UnitTest']
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<n98magerun_unittest_resource>', $configXmlContent);
        self::assertStringContainsString('<deprecatedNode>n98magerun_unittest_resource_eav_mysql4</deprecatedNode>', $configXmlContent);
        self::assertStringContainsString('<class>N98Magerun_UnitTest_Model_Resource</class>', $configXmlContent);
        self::assertStringContainsString('<entities>', $configXmlContent);
        self::assertStringContainsString('<entity1>', $configXmlContent);
        self::assertStringContainsString('<table>entity1table</table>', $configXmlContent);
        self::assertStringContainsString('<entity2>', $configXmlContent);
        self::assertStringContainsString('<table>entity2table</table>', $configXmlContent);
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
            ['command'         => $updateCommand->getName(), '--add-routers'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<admin>', $configXmlContent);
        self::assertStringContainsString('<routers>', $configXmlContent);
        self::assertStringContainsString('<n98magerun_unittest>', $configXmlContent);
        self::assertStringContainsString('<args>', $configXmlContent);
        self::assertStringContainsString('<use>standard</use>', $configXmlContent);
        self::assertStringContainsString('<module>n98magerun_unittest</module>', $configXmlContent);
        self::assertStringContainsString('<frontName>n98magerun</frontName>', $configXmlContent);
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
            ['command'         => $updateCommand->getName(), '--add-events'    => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<frontend>', $configXmlContent);
        self::assertStringContainsString('<events>', $configXmlContent);
        self::assertStringContainsString('<n98mageruntest_observer>', $configXmlContent);
        self::assertStringContainsString('<class>n98magerun_unittest/observer</class>', $configXmlContent);
        self::assertStringContainsString('<method>controllerActionPostdispatch</method>', $configXmlContent);
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
            ['command'              => $updateCommand->getName(), '--add-layout-updates' => true, 'vendorNamespace'      => 'N98Magerun', 'moduleName'           => 'UnitTest']
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<adminhtml>', $configXmlContent);
        self::assertStringContainsString('<layout>', $configXmlContent);
        self::assertStringContainsString('<updates>', $configXmlContent);
        self::assertStringContainsString('<n98magerun_unittest>', $configXmlContent);
        self::assertStringContainsString('<file>n98magerun_unittest.xml</file>', $configXmlContent);
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
            ['command'         => $updateCommand->getName(), '--add-translate' => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<adminhtml>', $configXmlContent);
        self::assertStringContainsString('<translate>', $configXmlContent);
        self::assertStringContainsString('<modules>', $configXmlContent);
        self::assertStringContainsString('<N98Magerun_UnitTest>', $configXmlContent);
        self::assertStringContainsString('<files>', $configXmlContent);
        self::assertStringContainsString('<default>N98magerun_UnitTest.csv</default>', $configXmlContent);
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
            ['command'         => $updateCommand->getName(), '--add-default'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest']
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        self::assertStringContainsString('<default>', $configXmlContent);
        self::assertStringContainsString('<sectiontest>', $configXmlContent);
        self::assertStringContainsString('<grouptest>', $configXmlContent);
        self::assertStringContainsString('<fieldname>fieldvalue</fieldname>', $configXmlContent);
    }
}
