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

        $this->assertContains('\'catalog\' instanceof \Mage_Catalog_Helper_Data', $fileContent);
        $this->assertContains('\'core/config\' instanceof \Mage_Core_Model_Config', $fileContent);
        if (class_exists('\Mage_Core_Model_Resource_Config')) { // since magento 1.7
            $this->assertContains('\'core/config\' instanceof \Mage_Core_Model_Resource_Config', $fileContent);
        }
        $this->assertContains('\'wishlist\' instanceof \Mage_Wishlist_Helper_Data', $fileContent);
        if (class_exists('\Mage_Core_Model_Resource_Helper_Mysql4')) {
            $this->assertContains('\'core\' instanceof \Mage_Core_Model_Resource_Helper_Mysql4', $fileContent);
        }
        $this->assertNotContains(
            '\'core/mysql4_design_theme_collection\' instanceof \Mage_Core_Model_Mysql4_Design_Theme_Collection',
            $fileContent
        );
        $this->assertNotContains(
            '\'payment/paygate_request\' instanceof \Mage_Payment_Model_Paygate_Request',
            $fileContent
        );
    }
}
