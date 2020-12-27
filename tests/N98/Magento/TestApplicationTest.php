<?php
/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

class TestApplicationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $application = new TestApplication($this);
        $this->assertInstanceOf(__NAMESPACE__ . '\TestApplication', $application);
    }

    /**
     * @test
     */
    public function magentoTestRoot()
    {
        $application = new TestApplication($this);
        $actual = $application->getTestMagentoRoot();
        $this->assertInternalType('string', $actual);
        $this->assertGreaterThan(10, strlen($actual));
        $this->assertDirectoryExists($actual);
    }

    /**
     * @test
     */
    public function getApplication()
    {
        $application = new TestApplication($this);
        $actual = $application->getApplication();
        $this->assertInstanceOf(__NAMESPACE__ . '\Application', $actual);
    }
}
