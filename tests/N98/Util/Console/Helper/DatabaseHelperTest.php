<?php

namespace N98\Util\Console\Helper;

use N98\Magento\Command\PHPUnit\TestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class DatabaseHelperTest
 *
 * @covers  N98\Util\Console\Helper\DatabaseHelper
 */
class DatabaseHelperTest extends TestCase
{
    /**
     * @return DatabaseHelper
     */
    protected function getHelper()
    {
        $command = $this->getApplication()->find('db:info');
        $command->getHelperSet()->setCommand($command);

        return $command->getHelper('database');
    }

    /**
     * @test
     */
    public function testHelperInstance()
    {
        $this->assertInstanceOf('\N98\Util\Console\Helper\DatabaseHelper', $this->getHelper());
    }

    /**
     * @test
     */
    public function getConnection()
    {
        $this->assertInstanceOf('\PDO', $this->getHelper()->getConnection());
    }

    /**
     * @test
     */
    public function dsn()
    {
        $this->assertStringStartsWith('mysql:', $this->getHelper()->dsn());
    }

    /**
     * @test
     */
    public function mysqlUserHasPrivilege()
    {
        $this->assertTrue($this->getHelper()->mysqlUserHasPrivilege('SELECT'));
    }

    /**
     * @test
     */
    public function getMysqlVariableValue()
    {
        $helper = $this->getHelper();

        // verify (complex) return value with existing global variable
        $actual = $helper->getMysqlVariableValue('version');

        $this->assertInternalType('array', $actual);
        $this->assertCount(1, $actual);
        $key = '@@version';
        $this->assertArrayHasKey($key, $actual);
        $this->assertInternalType('string', $actual[$key]);

        // quoted
        $actual = $helper->getMysqlVariableValue('`version`');
        $this->assertEquals('@@`version`', key($actual));

        // non-existent global variable
        try {
            $helper->getMysqlVariableValue('nonexistent');
            $this->fail('An expected exception has not been thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals("Failed to query mysql variable 'nonexistent'", $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function getMysqlVariable()
    {
        $helper = $this->getHelper();

        // behaviour with existing global variable
        $actual = $helper->getMysqlVariable('version');
        $this->assertInternalType('string', $actual);

        // behavior with existent session variable (INTEGER)
        $helper->getConnection()->query('SET @existent = 14;');
        $actual = $helper->getMysqlVariable('existent', '@');
        $this->assertSame("14", $actual);

        // behavior with non-existent session variable
        $actual = $helper->getMysqlVariable('nonexistent', '@');
        $this->assertNull($actual);

        // behavior with non-existent global variable
        try {
            $helper->getMysqlVariable('nonexistent');
            $this->fail('An expected Exception has not been thrown');
        } catch (RuntimeException $e) {
            // test against the mysql error message
            $this->assertStringEndsWith("SQLSTATE[HY000]: 1193: Unknown system variable 'nonexistent'", $e->getMessage());
        }

        // invalid variable type
        try {
            $helper->getMysqlVariable('nonexistent', '@@@');
            $this->fail('An expected Exception has not been thrown');
        } catch (InvalidArgumentException $e) {
            // test against the mysql error message
            $this->assertEquals(
                'Invalid mysql variable type "@@@", must be "@@" (system) or "@" (session)', $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function getTables()
    {
        $tables = $this->getHelper()->getTables();
        $this->assertInternalType('array', $tables);
        $this->assertContains('admin_user', $tables);
    }

    /**
     * @test
     */
    public function resolveTables()
    {
        $tables = $this->getHelper()->resolveTables(array('catalog\_*'));
        $this->assertContains('catalog_product_entity', $tables);
        $this->assertNotContains('catalogrule', $tables);

        $definitions = array(
            'test123' => array('tables'  => 'catalog\_*'),
            'dataflow' => array('tables' => 'dataflow_batch_import dataflow_batch_export')
        );

        $tables = $this->getHelper()->resolveTables(
            array('@test123', '@dataflow'),
            $definitions
        );
        $this->assertContains('catalog_product_entity', $tables);
        $this->assertContains('dataflow_batch_import', $tables);
        $this->assertNotContains('catalogrule', $tables);
    }
}
