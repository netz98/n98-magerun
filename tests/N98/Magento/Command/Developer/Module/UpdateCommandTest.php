<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\TestCase;
use N98\Util\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

final class UpdateCommandTest extends TestCase
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
            ['command'         => $createCommand->getName(), '--add-all'       => true, '--modman'        => true, '--description'   => 'Unit Test Description', '--author-name'   => 'Unit Test', '--author-email'  => 'n98-magerun@example.com', 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
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
    private function _deleteOldModule($root)
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

    private function getInputStream($input)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $input);

        rewind($stream);
        return $stream;
    }

    /**
     * @param $moduleBaseFolder
     * @return string
     */
    private function _getConfigXmlContents($moduleBaseFolder)
    {
        return file_get_contents($moduleBaseFolder . 'etc/config.xml');
    }

    /**
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _setVersionOptionTest($commandTester, $updateCommand, $moduleBaseFolder)
    {
        $commandTester->execute(
            ['command'         => $updateCommand->getName(), '--set-version'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
        );

        $this->assertFileExists($moduleBaseFolder . 'etc/config.xml');

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<version>2.0.0</version>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     * @return string
     */
    private function _addResourceModelOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("y\nentity1\nentity1table\nentity2\nentity2table\n\n"));
        $commandTester->execute(
            ['command'              => $updateCommand->getName(), '--add-resource-model' => true, 'vendorNamespace'      => 'N98Magerun', 'moduleName'           => 'UnitTest'],
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<n98magerun_unittest_resource>', $configXmlContent);
        $this->assertStringContainsString('<deprecatedNode>n98magerun_unittest_resource_eav_mysql4</deprecatedNode>', $configXmlContent);
        $this->assertStringContainsString('<class>N98Magerun_UnitTest_Model_Resource</class>', $configXmlContent);
        $this->assertStringContainsString('<entities>', $configXmlContent);
        $this->assertStringContainsString('<entity1>', $configXmlContent);
        $this->assertStringContainsString('<table>entity1table</table>', $configXmlContent);
        $this->assertStringContainsString('<entity2>', $configXmlContent);
        $this->assertStringContainsString('<table>entity2table</table>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _addRoutersOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("admin\nstandard\nn98magerun\n"));
        $commandTester->execute(
            ['command'         => $updateCommand->getName(), '--add-routers'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
        );

        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<admin>', $configXmlContent);
        $this->assertStringContainsString('<routers>', $configXmlContent);
        $this->assertStringContainsString('<n98magerun_unittest>', $configXmlContent);
        $this->assertStringContainsString('<args>', $configXmlContent);
        $this->assertStringContainsString('<use>standard</use>', $configXmlContent);
        $this->assertStringContainsString('<module>n98magerun_unittest</module>', $configXmlContent);
        $this->assertStringContainsString('<frontName>n98magerun</frontName>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _addEventsOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("frontend\ncontroller_action_postdispatch\nn98mageruntest_observer\nn98magerun_unittest/observer\ncontrollerActionPostdispatch"));
        $commandTester->execute(
            ['command'         => $updateCommand->getName(), '--add-events'    => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<frontend>', $configXmlContent);
        $this->assertStringContainsString('<events>', $configXmlContent);
        $this->assertStringContainsString('<n98mageruntest_observer>', $configXmlContent);
        $this->assertStringContainsString('<class>n98magerun_unittest/observer</class>', $configXmlContent);
        $this->assertStringContainsString('<method>controllerActionPostdispatch</method>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _addLayoutUpdatesOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("adminhtml\nn98magerun_unittest\nn98magerun_unittest.xml"));
        $commandTester->execute(
            ['command'              => $updateCommand->getName(), '--add-layout-updates' => true, 'vendorNamespace'      => 'N98Magerun', 'moduleName'           => 'UnitTest'],
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<adminhtml>', $configXmlContent);
        $this->assertStringContainsString('<layout>', $configXmlContent);
        $this->assertStringContainsString('<updates>', $configXmlContent);
        $this->assertStringContainsString('<n98magerun_unittest>', $configXmlContent);
        $this->assertStringContainsString('<file>n98magerun_unittest.xml</file>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _addTranslateOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("adminhtml\nN98magerun_UnitTest.csv"));
        $commandTester->execute(
            ['command'         => $updateCommand->getName(), '--add-translate' => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<adminhtml>', $configXmlContent);
        $this->assertStringContainsString('<translate>', $configXmlContent);
        $this->assertStringContainsString('<modules>', $configXmlContent);
        $this->assertStringContainsString('<N98Magerun_UnitTest>', $configXmlContent);
        $this->assertStringContainsString('<files>', $configXmlContent);
        $this->assertStringContainsString('<default>N98magerun_UnitTest.csv</default>', $configXmlContent);
    }

    /**
     * @param $dialog
     * @param $commandTester
     * @param $updateCommand
     * @param $moduleBaseFolder
     */
    private function _addDefaultOptionTest($dialog, $commandTester, $updateCommand, $moduleBaseFolder)
    {
        $dialog->setInputStream($this->getInputStream("sectiontest\ngrouptest\nfieldname\nfieldvalue"));
        $commandTester->execute(
            ['command'         => $updateCommand->getName(), '--add-default'   => true, 'vendorNamespace' => 'N98Magerun', 'moduleName'      => 'UnitTest'],
        );
        $configXmlContent = $this->_getConfigXmlContents($moduleBaseFolder);
        $this->assertStringContainsString('<default>', $configXmlContent);
        $this->assertStringContainsString('<sectiontest>', $configXmlContent);
        $this->assertStringContainsString('<grouptest>', $configXmlContent);
        $this->assertStringContainsString('<fieldname>fieldvalue</fieldname>', $configXmlContent);
    }
}
