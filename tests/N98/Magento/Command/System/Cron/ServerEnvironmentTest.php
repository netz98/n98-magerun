<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Application;
use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\TestCase;

final class ServerEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Initialise Magento autoloader (if not yet)
        $application = $this->getApplication();
        $this->assertInstanceOf(Application::class, $application);
    }

    public function testRegression()
    {
        $store = Mage::app()->getStore(null);
        $actual = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $this->assertIsString($actual);
        $this->assertMatchesRegularExpression('~/(ide-phpunit.php|phpunit)/$~', $actual);
    }

    public function testEnvironmentFix()
    {
        $store = Mage::app()->getStore(null);
        $store->resetConfig();

        $serverEnvironment = new ServerEnvironment();
        $serverEnvironment->initalize();

        $actual = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $this->assertIsString($actual);
        $this->assertStringEndsWith('/index.php/', $actual);

        $store->resetConfig();

        $serverEnvironment->reset();

        $actual = Mage::app()->getStore(null)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $this->assertIsString($actual);
        $this->assertMatchesRegularExpression('~/(ide-phpunit.php|phpunit)/$~', $actual);
    }
}
