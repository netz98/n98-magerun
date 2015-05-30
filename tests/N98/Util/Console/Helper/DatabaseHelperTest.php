<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;


class DatabaseHelperTest extends TestCase
{
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
        $this->assertContains('mysql:', $this->getHelper()->dsn());
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
