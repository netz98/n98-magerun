<?php

namespace N98\Util\Console\Helper;

use InvalidArgumentException;
use N98\Magento\Command\TestCase;
use RuntimeException;

/**
 * Class DatabaseHelperTest
 *
 * @covers  \N98\Util\Console\Helper\DatabaseHelper
 */
class DatabaseHelperTest extends TestCase
{
    /**
     * @var array of functions to call on teardown
     * @see tearDown()
     */
    private $tearDownRestore = array();

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
            $this->assertStringEndsWith(
                "SQLSTATE[HY000]: 1193: Unknown system variable 'nonexistent'",
                $e->getMessage()
            );
        }

        // invalid variable type
        try {
            $helper->getMysqlVariable('nonexistent', '@@@');
            $this->fail('An expected Exception has not been thrown');
        } catch (InvalidArgumentException $e) {
            // test against the mysql error message
            $this->assertEquals(
                'Invalid mysql variable type "@@@", must be "@@" (system) or "@" (session)',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function getTables()
    {
        $helper = $this->getHelper();

        $tables = $helper->getTables();
        $this->assertInternalType('array', $tables);
        $this->assertContains('admin_user', $tables);

        $dbSettings = $helper->getDbSettings();
        $ro = new \ReflectionObject($dbSettings);
        $rp = $ro->getProperty('config');
        $rp->setAccessible(true);

        $config = $rp->getValue($dbSettings);
        $previous = $config['prefix'];

        $this->tearDownRestore[] = function () use ($rp, $dbSettings, $previous) {
            $config['prefix'] = $previous;
            $rp->setValue($dbSettings, $config);
        };

        $config['prefix'] = $previous . 'core_';
        $rp->setValue($dbSettings, $config);

        $tables = $helper->getTables(null); // default value should be null-able and is false
        $this->assertInternalType('array', $tables);
        $this->assertNotContains('admin_user', $tables);
        $this->assertContains('core_store', $tables);
        $this->assertContains('core_website', $tables);

        $tables = $helper->getTables(true);
        $this->assertInternalType('array', $tables);
        $this->assertNotContains('admin_user', $tables);
        $this->assertContains('store', $tables);
        $this->assertContains('website', $tables);
    }

    /**
     * @test
     */
    public function resolveTables()
    {
        $tables = $this->getHelper()->resolveTables(array('catalog_*'));
        $this->assertContains('catalog_product_entity', $tables);
        $this->assertNotContains('catalogrule', $tables);

        $definitions = array(
            'wild_1'   => array('tables' => array('catalog_*')),
            'wild_2'   => array('tables' => array('core_config_dat?')),
            'dataflow' => array('tables' => array('dataflow_batch_import', 'dataflow_batch_export')),
        );

        $tables = $this->getHelper()->resolveTables(
            array('@wild_1', '@wild_2', '@dataflow'),
            $definitions
        );
        $this->assertContains('catalog_product_entity', $tables);
        $this->assertContains('core_config_data', $tables);
        $this->assertContains('dataflow_batch_import', $tables);
        $this->assertNotContains('catalogrule', $tables);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        foreach ($this->tearDownRestore as $restore) {
            $restore();
        }

        $restore = null;
        $this->tearDownRestore = null;

        parent::tearDown();
    }
}
