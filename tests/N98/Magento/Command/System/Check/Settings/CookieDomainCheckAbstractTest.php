<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Class CookieDomainCheckAbstractTest
 *
 * @covers N98\Magento\Command\System\Check\Settings\CookieDomainCheckAbstract
 */
final class CookieDomainCheckAbstractTest extends TestCase
{
    /**
     * @see validateCookieDomainAgainstUrl
     * @return \Iterator<(array<int, bool> | array<int, string>)>
     */
    public function provideCookieDomainsAndBaseUrls(): \Iterator
    {
        yield ['', '', false];
        yield ['https://www.example.com/', '', false];
        yield ['', '.example.com', false];
        yield ['https://www.example.com/', '.example.com', true];
        yield ['https://www.example.com/', 'www.example.com', true];
        yield ['https://images.example.com/', 'www.example.com', false];
        yield ['https://images.example.com/', 'example.com', true];
        yield ['https://images.example.com/', '.example.com', true];
        yield ['https://example.com/', '.example.com', false];
        yield ['https://www.example.com/', '.www.example.com', false];
        yield ['https://www.example.com/', 'wwww.example.com', false];
        yield ['https://www.example.com/', 'ww.example.com', false];
        yield ['https://www.example.com/', '.ww.example.com', false];
        yield ['https://www.example.com/', '.w.example.com', false];
        yield ['https://www.example.com/', '..example.com', false];
        // false-positives we know about, there is no check against public suffix list (the co.uk check)
        yield ['https://www.example.com/', '.com', false];
        yield ['https://www.example.co.uk/', '.co.uk', true];
        yield ['https://www.example.co.uk/', 'co.uk', true];
        // go cases <http://gertjans.home.xs4all.nl/javascript/cookies.html>
        yield ['http://go/', 'go', false];
        yield ['http://go/', '.go', false];
        yield ['http://go.go/', 'go', false];
        yield ['http://go.go/', '.go', false];
        # ... some edge-cases left out
        yield ['http://www.good.go/', '.good.go', true];
        yield ['http://www.good.go/', 'www.good.go', true];
        yield ['http://good.go/', 'www.good.go', false];
        yield ['http://also.good.go/', 'www.good.go', false];
    }

    /**
     * @dataProvider provideCookieDomainsAndBaseUrls
     */
    public function testValidateCookieDomainAgainstUrl($baseUrl, $cookieDomain, $expected)
    {
        /** @var CookieDomainCheckAbstract $stub */
        $stub = $this->getMockForAbstractClass(__NAMESPACE__ . '\CookieDomainCheckAbstract');

        $actual = $stub->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

        $message = sprintf('%s for %s', $cookieDomain, $baseUrl);

        $this->assertSame($expected, $actual, $message);
    }
}
