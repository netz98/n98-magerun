<?php

declare(strict_types=1);

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
final class DatabaseHelperTest extends TestCase
{
    /**
     * @var array of functions to call on teardown
     * @see tearDown()
     */
    private $tearDownRestore = [];

    /**
     * @return DatabaseHelper
     */
    private function getHelper()
    {
        $command = $this->getApplication()->find('db:info');
        $command->getHelperSet()->setCommand($command);

        return $command->getHelper('database');
    }

    public function testHelperInstance()
    {
        $this->assertInstanceOf(DatabaseHelper::class, $this->getHelper());
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf(PDO::class, $this->getHelper()->getConnection());
    }

    public function testDsn()
    {
        $this->assertStringStartsWith('mysql:', $this->getHelper()->dsn());
    }

    public function testMysqlUserHasPrivilege()
    {
        $this->assertTrue($this->getHelper()->mysqlUserHasPrivilege('SELECT'));
    }

    public function testGetMysqlVariableValue()
    {
        $databaseHelper = $this->getHelper();

        // verify (complex) return value with existing global variable
        $actual = $databaseHelper->getMysqlVariableValue('version');

        $this->assertIsArray($actual);
        $this->assertCount(1, $actual);
        $key = '@@version';
        $this->assertArrayHasKey($key, $actual);
        $this->assertIsString($actual[$key]);

        // quoted
        $actual = $databaseHelper->getMysqlVariableValue('`version`');
        $this->assertSame('@@`version`', key($actual));

        // non-existent global variable
        try {
            $databaseHelper->getMysqlVariableValue('nonexistent');
            self::fail('An expected exception has not been thrown');
        } catch (RuntimeException $runtimeException) {
            // do nothing -> We need to check different strings for old MySQL and MariaDB servers
            //self::assertEquals("SQLSTATE[HY000]: General error: 1193 Unknown system variable 'nonexistent'", $runtimeException->getMessage());
        }
    }

    public function testGetMysqlVariable()
    {
        $databaseHelper = $this->getHelper();

        // behaviour with existing global variable
        $actual = $databaseHelper->getMysqlVariable('version');
        $this->assertIsString($actual);

        // behavior with existent session variable (INTEGER)
        $databaseHelper->getConnection()->query('SET @existent = 14;');
        $actual = $databaseHelper->getMysqlVariable('existent', '@');
        # $this->assertSame(14, $actual);
        $this->assertNotNull($actual);

        // behavior with non-existent session variable
        $actual = $databaseHelper->getMysqlVariable('nonexistent', '@');
        $this->assertNull($actual);

        // behavior with non-existent global variable
        /*
         * MySQL vs. MariaDB -> Different error codes
        try {
            $helper->getMysqlVariable('nonexistent');
            self::fail('An expected Exception has not been thrown');
        } catch (RuntimeException $runtimeException) {
            // test against the mysql error message
            self::assertStringEndsWith(
                "SQLSTATE[HY000]: General error: 1193 Unknown system variable 'nonexistent'",
                $runtimeException->getMessage()
            );
        }*/

        // invalid variable type
        try {
            $databaseHelper->getMysqlVariable('nonexistent', '@@@');
            self::fail('An expected Exception has not been thrown');
        } catch (InvalidArgumentException $invalidArgumentException) {
            // test against the mysql error message
            $this->assertSame('Invalid mysql variable type "@@@", must be "@@" (system) or "@" (session)', $invalidArgumentException->getMessage());
        }
    }

    public function testGetTables()
    {
        $databaseHelper = $this->getHelper();

        $tables = $databaseHelper->getTables();
        $this->assertIsArray($tables);
        $this->assertContains('admin_user', $tables);

        $dbSettings = $databaseHelper->getDbSettings();
        $reflectionObject = new ReflectionObject($dbSettings);
        $reflectionProperty = $reflectionObject->getProperty('config');
        $reflectionProperty->setAccessible(true);

        $config = $reflectionProperty->getValue($dbSettings);
        $previous = $config['prefix'];

        $this->tearDownRestore[] = function () use ($reflectionProperty, $dbSettings, $previous): void {
            $config = [];
            $config['prefix'] = $previous;
            $reflectionProperty->setValue($dbSettings, $config);
        };

        $config['prefix'] = $previous . 'core_';
        $reflectionProperty->setValue($dbSettings, $config);

        $tables = $databaseHelper->getTables(null); // default value should be null-able and is false
        $this->assertIsArray($tables);
        $this->assertNotContains('admin_user', $tables);
        $this->assertContains('core_store', $tables);
        $this->assertContains('core_website', $tables);

        $tables = $databaseHelper->getTables(true);
        $this->assertIsArray($tables);
        $this->assertNotContains('admin_user', $tables);
        $this->assertContains('store', $tables);
        $this->assertContains('website', $tables);
    }

    public function testResolveTables()
    {
        $tables = $this->getHelper()->resolveTables(['catalog_*']);
        $this->assertContains('catalog_product_entity', $tables);
        $this->assertNotContains('catalogrule', $tables);

        $definitions = ['wild_1'   => ['tables' => ['catalog_*']], 'wild_2'   => ['tables' => ['core_config_dat?']], 'dataflow' => ['tables' => ['dataflow_batch_import', 'dataflow_batch_export']]];

        $tables = $this->getHelper()->resolveTables(
            ['@wild_1', '@wild_2', '@dataflow'],
            $definitions,
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
    protected function tearDown(): void
    {
        foreach ($this->tearDownRestore as $singleTearDownRestore) {
            $singleTearDownRestore();
        }

        $this->tearDownRestore = null;

        parent::tearDown();
    }
}
