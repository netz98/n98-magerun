<?php

namespace N98\Magento\Command\Database;

use N98\Magento\Command\TestCase;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @see \N98\Magento\Command\Database\DumpCommand
 */
class DumpCommandTest extends TestCase
{
    /**
     * @return Command
     */
    protected function getCommand()
    {
        $dumpCommand = new DumpCommand();
        if (!$dumpCommand->isEnabled()) {
            $this->markTestSkipped('DumpCommand is not enabled.');
        }

        $application = $this->getApplication();
        $application->add($dumpCommand);
        $command = $this->getApplication()->find('db:dump');

        return $command;
    }

    public function testExecute()
    {
        $command = $this->getCommand();

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--compression'  => 'gz',
            )
        );

        $this->assertRegExp('/mysqldump/', $commandTester->getDisplay());
        $this->assertRegExp('/\.sql/', $commandTester->getDisplay());
        $this->assertContains(".sql.gz", $commandTester->getDisplay());
    }

    /**
     * @see filenamePatterns
     */
    public function provideFilenamePatternsAndOptions()
    {
        return array(
            # testAddTimeAutogenerated
            array('/^.*[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql$/', array()),
            # testAddTimePrefixAutogenerated
            array('/^[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}.*\.sql$/', array('--add-time' => 'prefix', )),
            # testAddTimeFilenameSpecified
            array('/^.*[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql.gz$/', array('--compression' => 'gzip', )),
            # testAddTimeFilenameSpecified
            array('/^foo_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql$/', array('filename' => 'foo.sql', )),
            # testAddTimePrefixFilenameSpecified
            array(
                '/^[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}_foo\.sql$/',
                array('filename' => 'foo.sql', '--add-time' => 'prefix', ),
            ),
            # testAddTimeOffFilenameSpecified
            array('/^foo.sql$/', array('filename' => 'foo.sql', '--add-time' => false, )),
            # testAddTimeFilenameSpecifiedRelative
            array('/^..\/foo_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.sql$/', array('filename' => '../foo.sql', )),
        );
    }

    /**
     * @test
     * @dataProvider provideFilenamePatternsAndOptions
     *
     * @param string $regex
     * @param array $options
     * @return void
     */
    public function filenamePatterns($regex, array $options)
    {
        $command = $this->getCommand();

        $mandatory = array(
            'command'               => $command->getName(),
            '--force'               => true,
            '--print-only-filename' => true,
            '--dry-run'             => null,
        );

        $defaults = array(
            '--add-time' => true,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute($mandatory + $options + $defaults);
        $this->assertRegExp($regex, $commandTester->getDisplay());
    }

    public function testWithStripOption()
    {
        $command = $this->getCommand();

        $this->getApplication()->initMagento();

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--strip'        => '@development not_existing_table_1',
                '--compression'  => 'gzip',
            )
        );

        $dbConfig = $this->getDatabaseConnection()->getConfig();
        $db = $dbConfig['dbname'];

        $this->assertRegExp("/--ignore-table=$db.customer_entity/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.customer_address_entity/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order_item/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order_item/", $commandTester->getDisplay());
        $this->assertNotContains("not_existing_table_1", $commandTester->getDisplay());
        $this->assertContains(".sql.gz", $commandTester->getDisplay());

        /**
         * Uncompressed
         */
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--strip'        => '@development',
            )
        );
        $this->assertNotContains(".sql.gz", $commandTester->getDisplay());
    }

    public function testWithIncludeExcludeOptions()
    {
        $command = $this->getCommand();
        $this->getApplication()->initMagento();
        $dbConfig = $this->getDatabaseConnection()->getConfig();
        $db = $dbConfig['dbname'];

        /**
         * Exclude
         */
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--exclude'      => 'core_config_data',
                '--compression'  => 'gzip',
            )
        );
        $this->assertRegExp("/--ignore-table=$db\.core_config_data/", $commandTester->getDisplay());

        /**
         * Include
         */
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--include'      => 'core_config_data',
                '--compression'  => 'gzip',
            )
        );
        $this->assertNotRegExp("/--ignore-table=$db\.core_config_data/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db\.catalog_product_entity/", $commandTester->getDisplay());
    }

    public function testIncludeExcludeMutualExclusivity()
    {
        /**
         * Both include and exclude.
         */
        $command = $this->getCommand();
        $this->getApplication()->initMagento();
        $this->setExpectedException('InvalidArgumentException', 'Cannot specify both include and exclude parameters.');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'        => $command->getName(),
                '--add-time'     => true,
                '--only-command' => true,
                '--force'        => true,
                '--include'      => 'core_config_data',
                '--exclude'      => 'catalog_product_entity',
                '--compression'  => 'gzip',
            )
        );
    }

    /**
     * @test
     * @link https://github.com/netz98/n98-magerun2/issues/200
     */
    public function realDump()
    {
        $dumpFile = new SplFileInfo($this->getTestMagentoRoot() . '/test-dump.sql');
        if ($dumpFile->isReadable()) {
            $this->assertTrue(unlink($dumpFile), 'Precondition to unlink that the file does not exists');
        }
        $this->assertFalse(is_readable($dumpFile), 'Precondition that the file does not exists');

        $command = $this->getCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--strip'  => '@stripped',
                'filename' => $dumpFile,
            )
        );

        $this->assertTrue($dumpFile->isReadable(), 'File was created');
        // dump should be larger than quarter a megabyte
        $this->assertGreaterThan(250000, $dumpFile->getSize());
    }
}
