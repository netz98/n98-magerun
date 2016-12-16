<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use N98\Magento\Command\TestCase;

/**
 * Class DbSettingsTest
 *
 * @cover  N98\Magento\DbSettings
 *
 * @package N98\Magento
 */
class DbSettingsTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $file = $this->getTestMagentoRoot() . '/app/etc/local.xml';
        $settings = new DbSettings($file);
        $this->assertInstanceOf(__NAMESPACE__ . '\\DbSettings', $settings);
    }

    /**
     * @test
     */
    public function settings()
    {
        $file = __DIR__ . '/local.xml';

        $settings = new DbSettings($file);

        $this->assertSame('', $settings->getTablePrefix());

        $this->assertSame('localhost', $settings->getHost());
        $this->assertNull($settings->getPort());

        $this->assertNull($settings->getUnixSocket());

        $this->assertSame('user', $settings->getUsername());
        $this->assertSame('pass', $settings->getPassword());

        // DbSettings is more strict here, only using known DSN settings, see @link http://php.net/ref.pdo-mysql.connection
        // minus those settings that are black-listed: dbname, charset
        // "mysql:host=localhost;initStatements=SET NAMES utf8;model=mysql4;type=pdo_mysql;pdoType=;active=1;prefix="
        $this->assertEquals('mysql:host=localhost', $settings->getDsn());
    }

    /**
     * @test
     */
    public function arrayAccess()
    {
        $file = __DIR__ . '/local.xml';
        $settings = new DbSettings($file);

        $this->assertSame('user', $settings['username']);
        $this->assertSame('pass', $settings['password']);

        // unix_socket should be NULL
        $this->assertNull($settings['unix_socket']);

        // it's still leaky:
        $this->assertInstanceOf('SimpleXMLElement', $settings['pdoType']);
    }
}
