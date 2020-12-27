<?php

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MetaCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MetaCommand());
        $command = $this->getApplication()->find('dev:ide:phpstorm:meta');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'  => $command->getName(),
                '--stdout' => true,
            )
        );

        $fileContent = $commandTester->getDisplay(true);

        self::assertContains('\'catalog\' => \Mage_Catalog_Helper_Data', $fileContent);
        self::assertContains('\'core/config\' => \Mage_Core_Model_Config', $fileContent);

        if (class_exists('\Mage_Core_Model_Resource_Config')) { // since magento 1.7
            self::assertContains('\'core/config\' => \Mage_Core_Model_Resource_Config', $fileContent);
        }

        self::assertContains('\'wishlist\' => \Mage_Wishlist_Helper_Data', $fileContent);

        if (class_exists('\Mage_Core_Model_Resource_Helper_Mysql4')) {
            self::assertContains('\'core\' => \Mage_Core_Model_Resource_Helper_Mysql4', $fileContent);
        }

        self::assertNotContains(
            '\'payment/paygate_request\' => \Mage_Payment_Model_Paygate_Request',
            $fileContent
        );
    }
}
