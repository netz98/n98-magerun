<?php

namespace N98\Magento\Command\System\Cron;

use N98\Magento\Application;
use Mage;
use Mage_Core_Model_Store;
use N98\Magento\Command\TestCase;

class ServerEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Initialise Magento autoloader (if not yet)
        $application = $this->getApplication();
        self::assertInstanceOf(Application::class, $application);
    }

    /**
     * @test that getBaseUrl contains the script-name (here: Phpunit runner)
     */
    public function regression()
    {
        $store = Mage::app()->getStore(null);
        $actual = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        self::assertIsString($actual);
        self::assertMatchesRegularExpression('~/(ide-phpunit.php|phpunit)/$~', $actual);
    }

    /**
     * @test
     */
    public function environmentFix()
    {
        $store = Mage::app()->getStore(null);
        $store->resetConfig();

        $environment = new ServerEnvironment();
        $environment->initalize();

        $actual = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        self::assertIsString($actual);
        self::assertStringEndsWith('/index.php/', $actual);

        $store->resetConfig();

        $environment->reset();

        $actual = Mage::app()->getStore(null)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        self::assertIsString($actual);
        self::assertMatchesRegularExpression('~/(ide-phpunit.php|phpunit)/$~', $actual);
    }
}
