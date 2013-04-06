<?php

namespace N98\Magento\Command\Developer\Ide\PhpStorm;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

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
                'command' => $command->getName()
            )
        );

        $generatedFile = $this->getApplication()->getMagentoRootFolder() . '/.phpstorm.meta.php';
        $this->assertFileExists($generatedFile);
        $fileContent = file_get_contents($generatedFile);
        $this->assertContains('\'catalog\' instanceof \Mage_Catalog_Helper_Data', $fileContent);
        $this->assertContains('\'core/config\' instanceof \Mage_Core_Model_Config', $fileContent);
        $this->assertContains('\'core/config\' instanceof \Mage_Core_Model_Resource_Config', $fileContent);
        $this->assertContains('\'wishlist\' instanceof \Mage_Wishlist_Helper_Data', $fileContent);
    }
}