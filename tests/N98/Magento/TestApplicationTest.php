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
        self::assertInstanceOf(__NAMESPACE__ . '\TestApplication', $application);
    }

    /**
     * @test
     */
    public function magentoTestRoot()
    {
        $application = new TestApplication($this);
        $actual = $application->getTestMagentoRoot();
        self::assertInternalType('string', $actual);
        self::assertGreaterThan(10, strlen($actual));
        self::assertDirectoryExists($actual);
    }

    /**
     * @test
     */
    public function getApplication()
    {
        $application = new TestApplication($this);
        $actual = $application->getApplication();
        self::assertInstanceOf(__NAMESPACE__ . '\Application', $actual);
    }
}
