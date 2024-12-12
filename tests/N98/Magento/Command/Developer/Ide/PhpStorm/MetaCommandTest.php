<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MetaCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new MetaCommand());

        $command = $this->getApplication()->find('dev:ide:phpstorm:meta');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'  => $command->getName(), '--stdout' => true],
        );

        $fileContent = $commandTester->getDisplay(true);

        $this->assertStringContainsString('\'catalog\' => \Mage_Catalog_Helper_Data', $fileContent);
        $this->assertStringContainsString('\'core/config\' => \Mage_Core_Model_Config', $fileContent);

        if (class_exists('\Mage_Core_Model_Resource_Config')) { // since magento 1.7
            $this->assertStringContainsString('\'core/config\' => \Mage_Core_Model_Resource_Config', $fileContent);
        }

        $this->assertStringContainsString('\'wishlist\' => \Mage_Wishlist_Helper_Data', $fileContent);

        if (class_exists('\Mage_Core_Model_Resource_Helper_Mysql4')) {
            $this->assertStringContainsString('\'core\' => \Mage_Core_Model_Resource_Helper_Mysql4', $fileContent);
        }

        $this->assertStringNotContainsString('\'payment/paygate_request\' => \Mage_Payment_Model_Paygate_Request', $fileContent);
    }
}
