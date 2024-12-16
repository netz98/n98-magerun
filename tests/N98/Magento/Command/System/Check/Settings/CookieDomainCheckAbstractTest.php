<?php
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
class CookieDomainCheckAbstractTest extends TestCase
{
    /**
     * @see validateCookieDomainAgainstUrl
     */
    public function provideCookieDomainsAndBaseUrls()
    {
        return [
            ['', '', false],
            ['https://www.example.com/', '', false],
            ['', '.example.com', false],
            ['https://www.example.com/', '.example.com', true],
            ['https://www.example.com/', 'www.example.com', true],
            ['https://images.example.com/', 'www.example.com', false],
            ['https://images.example.com/', 'example.com', true],
            ['https://images.example.com/', '.example.com', true],
            ['https://example.com/', '.example.com', false],
            ['https://www.example.com/', '.www.example.com', false],
            ['https://www.example.com/', 'wwww.example.com', false],
            ['https://www.example.com/', 'ww.example.com', false],
            ['https://www.example.com/', '.ww.example.com', false],
            ['https://www.example.com/', '.w.example.com', false],
            ['https://www.example.com/', '..example.com', false],
            // false-positives we know about, there is no check against public suffix list (the co.uk check)
            ['https://www.example.com/', '.com', false],
            ['https://www.example.co.uk/', '.co.uk', true],
            ['https://www.example.co.uk/', 'co.uk', true],
            // go cases <http://gertjans.home.xs4all.nl/javascript/cookies.html>
            ['http://go/', 'go', false],
            ['http://go/', '.go', false],
            ['http://go.go/', 'go', false],
            ['http://go.go/', '.go', false],
            # ... some edge-cases left out
            ['http://www.good.go/', '.good.go', true],
            ['http://www.good.go/', 'www.good.go', true],
            ['http://good.go/', 'www.good.go', false],
            ['http://also.good.go/', 'www.good.go', false],
        ];
    }

    /**
     * @test
     * @dataProvider provideCookieDomainsAndBaseUrls
     */
    public function validateCookieDomainAgainstUrl($baseUrl, $cookieDomain, $expected)
    {
        /** @var CookieDomainCheckAbstract $stub */
        $stub = $this->getMockForAbstractClass(__NAMESPACE__ . '\CookieDomainCheckAbstract');

        $actual = $stub->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

        $message = sprintf('%s for %s', $cookieDomain, $baseUrl);

        self::assertSame($expected, $actual, $message);
    }
}
