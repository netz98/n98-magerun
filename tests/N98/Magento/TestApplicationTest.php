<?php

declare(strict_types=1);

/*
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use PHPUnit\Framework\TestCase;

final class TestApplicationTest extends TestCase
{
    public function testCreation()
    {
        $testApplication = new TestApplication($this);
        $this->assertInstanceOf(__NAMESPACE__ . '\TestApplication', $testApplication);
    }

    public function testMagentoTestRoot()
    {
        $testApplication = new TestApplication($this);
        $actual = $testApplication->getTestMagentoRoot();
        $this->assertIsString($actual);
        $this->assertGreaterThan(10, strlen($actual));
        $this->assertDirectoryExists($actual);
    }

    public function testGetApplication()
    {
        $testApplication = new TestApplication($this);
        $actual = $testApplication->getApplication();
        $this->assertInstanceOf(__NAMESPACE__ . '\Application', $actual);
    }
}
