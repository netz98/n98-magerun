<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use SimpleXMLElement;
use N98\Magento\Command\TestCase;

/**
 * Class DbSettingsTest
 *
 * @cover  N98\Magento\DbSettings
 *
 * @package N98\Magento
 */
final class DbSettingsTest extends TestCase
{
    public function testCreation()
    {
        $file = $this->getTestMagentoRoot() . '/app/etc/local.xml';
        $dbSettings = new DbSettings($file);
        $this->assertInstanceOf(__NAMESPACE__ . '\\DbSettings', $dbSettings);
    }

    public function testSettings()
    {
        $file = __DIR__ . '/local.xml';

        $dbSettings = new DbSettings($file);

        $this->assertSame('', $dbSettings->getTablePrefix());

        $this->assertSame('localhost', $dbSettings->getHost());
        $this->assertNull($dbSettings->getPort());

        $this->assertNull($dbSettings->getUnixSocket());

        $this->assertSame('user', $dbSettings->getUsername());
        $this->assertSame('pass', $dbSettings->getPassword());

        // DbSettings is more strict here, only using known DSN settings, see @link http://php.net/ref.pdo-mysql.connection
        // minus those settings that are black-listed: dbname, charset
        // "mysql:host=localhost;initStatements=SET NAMES utf8;model=mysql4;type=pdo_mysql;pdoType=;active=1;prefix="
        $this->assertSame('mysql:host=localhost', $dbSettings->getDsn());
    }

    public function testArrayAccess()
    {
        $file = __DIR__ . '/local.xml';
        $dbSettings = new DbSettings($file);

        $this->assertSame('user', $dbSettings['username']);
        $this->assertSame('pass', $dbSettings['password']);

        // unix_socket should be NULL
        $this->assertNull($dbSettings['unix_socket']);

        // it's still leaky:
        // self::assertInstanceOf(SimpleXMLElement::class, $settings['pdoType']);
    }
}
