<?php

namespace N98\Util\Console\Helper;

use PDO;
use ReflectionObject;
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
    private $tearDownRestore = [];

    /**
     * @return DatabaseHelper
     */
    protected function getHelper()
    {
        $command = $this->getApplication()->find('db:info');
        $command->getHelperSet()->setCommand($command);

        return $command->getHelper('database');
    }

    public function testHelperInstance()
    {
        self::assertInstanceOf(DatabaseHelper::class, $this->getHelper());
    }

    /**
     * @test
     */
    public function getConnection()
    {
        self::assertInstanceOf(PDO::class, $this->getHelper()->getConnection());
    }

    /**
     * @test
     */
    public function dsn()
    {
        self::assertStringStartsWith('mysql:', $this->getHelper()->dsn());
    }

    /**
     * @test
     */
    public function mysqlUserHasPrivilege()
    {
        self::assertTrue($this->getHelper()->mysqlUserHasPrivilege('SELECT'));
    }

    /**
     * @test
     */
    public function getMysqlVariableValue()
    {
        $helper = $this->getHelper();

        // verify (complex) return value with existing global variable
        $actual = $helper->getMysqlVariableValue('version');

        self::assertIsArray($actual);
        self::assertCount(1, $actual);
        $key = '@@version';
        self::assertArrayHasKey($key, $actual);
        self::assertIsString($actual[$key]);

        // quoted
        $actual = $helper->getMysqlVariableValue('`version`');
        self::assertEquals('@@`version`', key($actual));

        // non-existent global variable
        try {
            $helper->getMysqlVariableValue('nonexistent');
            self::fail('An expected exception has not been thrown');
        } catch (RuntimeException $runtimeException) {
            self::assertEquals("Failed to query mysql variable 'nonexistent'", $runtimeException->getMessage());
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
        self::assertIsString($actual);

        // behavior with existent session variable (INTEGER)
        $helper->getConnection()->query('SET @existent = 14;');
        $actual = $helper->getMysqlVariable('existent', '@');
        self::assertSame("14", $actual);

        // behavior with non-existent session variable
        $actual = $helper->getMysqlVariable('nonexistent', '@');
        self::assertNull($actual);

        // behavior with non-existent global variable
        try {
            $helper->getMysqlVariable('nonexistent');
            self::fail('An expected Exception has not been thrown');
        } catch (RuntimeException $runtimeException) {
            // test against the mysql error message
            self::assertStringEndsWith(
                "SQLSTATE[HY000]: 1193: Unknown system variable 'nonexistent'",
                $runtimeException->getMessage()
            );
        }

        // invalid variable type
        try {
            $helper->getMysqlVariable('nonexistent', '@@@');
            self::fail('An expected Exception has not been thrown');
        } catch (InvalidArgumentException $invalidArgumentException) {
            // test against the mysql error message
            self::assertEquals(
                'Invalid mysql variable type "@@@", must be "@@" (system) or "@" (session)',
                $invalidArgumentException->getMessage()
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
        self::assertIsArray($tables);
        self::assertContains('admin_user', $tables);

        $dbSettings = $helper->getDbSettings();
        $reflectionObject = new ReflectionObject($dbSettings);
        $reflectionProperty = $reflectionObject->getProperty('config');
        $reflectionProperty->setAccessible(true);

        $config = $reflectionProperty->getValue($dbSettings);
        $previous = $config['prefix'];

        $this->tearDownRestore[] = function () use ($reflectionProperty, $dbSettings, $previous) {
            $config = [];
            $config['prefix'] = $previous;
            $reflectionProperty->setValue($dbSettings, $config);
        };

        $config['prefix'] = $previous . 'core_';
        $reflectionProperty->setValue($dbSettings, $config);

        $tables = $helper->getTables(null); // default value should be null-able and is false
        self::assertIsArray($tables);
        self::assertNotContains('admin_user', $tables);
        self::assertContains('core_store', $tables);
        self::assertContains('core_website', $tables);

        $tables = $helper->getTables(true);
        self::assertIsArray($tables);
        self::assertNotContains('admin_user', $tables);
        self::assertContains('store', $tables);
        self::assertContains('website', $tables);
    }

    /**
     * @test
     */
    public function resolveTables()
    {
        $tables = $this->getHelper()->resolveTables(['catalog_*']);
        self::assertContains('catalog_product_entity', $tables);
        self::assertNotContains('catalogrule', $tables);

        $definitions = ['wild_1'   => ['tables' => ['catalog_*']], 'wild_2'   => ['tables' => ['core_config_dat?']], 'dataflow' => ['tables' => ['dataflow_batch_import', 'dataflow_batch_export']]];

        $tables = $this->getHelper()->resolveTables(
            ['@wild_1', '@wild_2', '@dataflow'],
            $definitions
        );
        self::assertContains('catalog_product_entity', $tables);
        self::assertContains('core_config_data', $tables);
        self::assertContains('dataflow_batch_import', $tables);
        self::assertNotContains('catalogrule', $tables);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        foreach ($this->tearDownRestore as $singleTearDownRestore) {
            $singleTearDownRestore();
        }
        $this->tearDownRestore = null;

        parent::tearDown();
    }
}
