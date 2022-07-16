<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace N98\Magento\Command\Composer;

use InvalidArgumentException;
use Composer\Command\InitCommand;
use N98\Magento\Command\TestCase;

class InitCommandTest extends TestCase
{
    public function testParseValidAuthorString()
    {
        $command = new InitCommand();
        $author = $command->parseAuthorString('John Smith <john@example.com>');
        self::assertEquals('John Smith', $author['name']);
        self::assertEquals('john@example.com', $author['email']);
    }

    public function testParseValidUtf8AuthorString()
    {
        $command = new InitCommand();
        $author = $command->parseAuthorString('Matti Meikäläinen <matti@example.com>');
        self::assertEquals('Matti Meikäläinen', $author['name']);
        self::assertEquals('matti@example.com', $author['email']);
    }

    public function testParseEmptyAuthorString()
    {
        $command = new InitCommand();
        $this->expectException(InvalidArgumentException::class);
        $command->parseAuthorString('');
    }

    public function testParseAuthorStringWithInvalidEmail()
    {
        $command = new InitCommand();
        $this->expectException(InvalidArgumentException::class);
        $command->parseAuthorString('John Smith <john>');
    }
}
