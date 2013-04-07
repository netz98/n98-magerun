<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class DumpCommandTest extends TestCase
{
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
                '--compression'  => 'gz'
            )
        );
    
        $this->assertRegExp('/mysqldump/', $commandTester->getDisplay());
        $this->assertRegExp('/\.sql/', $commandTester->getDisplay());
        $this->assertContains(".sql.gz", $commandTester->getDisplay());
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
                '--strip'        => '@development',
                '--compression'  => 'gzip'
            )
        );

        $dbConfig = $this->getDatabaseConnection()->getConfig();
        $db = $dbConfig['dbname'];

        $this->assertRegExp("/--ignore-table=$db.customer_entity/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.customer_address_entity/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order_item/", $commandTester->getDisplay());
        $this->assertRegExp("/--ignore-table=$db.sales_flat_order_item/", $commandTester->getDisplay());
        $this->assertContains(".sql.gz", $commandTester->getDisplay());
    }

    /**
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function getCommand()
    {
        $application = $this->getApplication();
        $application->add(new DumpCommand());
        $command = $this->getApplication()->find('db:dump');

        return $command;
    }

}