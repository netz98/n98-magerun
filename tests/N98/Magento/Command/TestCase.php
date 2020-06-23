<?php

namespace N98\Magento\Command;

use N98\Magento\Application;
use N98\Magento\MagerunCommandTester;
use N98\Magento\TestApplication;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class TestCase
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\PHPUnit
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestApplication
     */
    private $testApplication;

    /**
     * getter for the magento root directory of the test-suite
     *
     * @see ApplicationTest::testExecute
     *
     * @return string
     */
    public function getTestMagentoRoot()
    {
        return $this->getTestApplication()->getTestMagentoRoot();
    }

    /**
     * @return Application|PHPUnit_Framework_MockObject_MockObject
     */
    public function getApplication()
    {
        return $this->getTestApplication()->getApplication();
    }

    /**
     * @return \Varien_Db_Adapter_Pdo_Mysql
     */
    public function getDatabaseConnection()
    {
        $resource = \Mage::getSingleton('core/resource');

        return $resource->getConnection('write');
    }

    /**
     * @return TestApplication
     */
    private function getTestApplication()
    {
        if (null === $this->testApplication) {
            $this->testApplication = new TestApplication();
        }

        return $this->testApplication;
    }

    /**
     * @var array
     */
    private $testers = array();

    /**
     * @param string|array $command name or input
     * @return MagerunCommandTester
     */
    private function getMagerunTester($command)
    {
        if (is_string($command)) {
            $input = array(
                'command' => $command,
            );
        } else {
            $input = $command;
        }

        $hash = md5(json_encode($input));
        if (!isset($this->testers[$hash])) {
            $this->testers[$hash] = new MagerunCommandTester($this, $input);
        }

        return $this->testers[$hash];
    }

    /**
     * @param string|array $command actual command to execute and obtain the display (output) from
     * @param string $needle string within the display
     * @param string $message [optional]
     */
    protected function assertDisplayContains($command, $needle, $message = "")
    {
        $display = $this->getMagerunTester($command)->getDisplay();

        $this->assertContains($needle, $display, $message);
    }

    /**
     * @param string|array $command actual command to execute and obtain the display (output) from
     * @param string $needle string within the display
     * @param string $message [optional]
     */
    protected function assertDisplayNotContains($command, $needle, $message = "")
    {
        $display = $this->getMagerunTester($command)->getDisplay();

        $this->assertNotContains($needle, $display, $message);
    }

    /**
     * @param string|array $command
     * @param string $pattern
     * @param string $message [optional]
     */
    protected function assertDisplayRegExp($command, $pattern, $message = "")
    {
        $display = $this->getMagerunTester($command)->getDisplay();

        $this->assertRegExp($pattern, $display, $message);
    }

    /**
     * Command executes with a status code of zero
     *
     * @param string|array $command
     * @param string $message
     * @return MagerunCommandTester
     */
    protected function assertExecute($command, $message = "")
    {
        $tester = $this->getMagerunTester($command);
        $status = $tester->getStatus();

        if (strlen($message)) {
            $message .= "\n";
        }

        $message .= "Command executes with a status code of zero";

        $this->assertSame(0, $status, $message);

        return $tester;
    }
}
