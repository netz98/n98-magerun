<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\Settings;

/**
 * Class CookieDomainCheckAbstractTest
 *
 * @covers N98\Magento\Command\System\Check\Settings\CookieDomainCheckAbstract
 */
class CookieDomainCheckAbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @see validateCookieDomainAgainstUrl
     */
    public function provideCookieDomainsAndBaseUrls()
    {
        return array(
            array("", "", false),
            array("https://www.example.com/", "", false),
            array("", ".example.com", false),
            array("https://www.example.com/", ".example.com", true),
            array("https://www.example.com/", "www.example.com", true),

            array("https://images.example.com/", "www.example.com", false),
            array("https://images.example.com/", "example.com", true),
            array("https://images.example.com/", ".example.com", true),
            array("https://example.com/", ".example.com", false),

            array("https://www.example.com/", ".www.example.com", false),
            array("https://www.example.com/", "wwww.example.com", false),
            array("https://www.example.com/", "ww.example.com", false),
            array("https://www.example.com/", ".ww.example.com", false),
            array("https://www.example.com/", ".w.example.com", false),
            array("https://www.example.com/", "..example.com", false),

            // false-positives we know about, there is no check against public suffix list (the co.uk check)
            array("https://www.example.com/", ".com", false),
            array("https://www.example.co.uk/", ".co.uk", true),
            array("https://www.example.co.uk/", "co.uk", true),

            // go cases <http://gertjans.home.xs4all.nl/javascript/cookies.html>
            array('http://go/', 'go', false),
            array('http://go/', '.go', false),
            array('http://go.go/', 'go', false),
            array('http://go.go/', '.go', false),
            # ... some edge-cases left out
            array('http://www.good.go/', '.good.go', true),
            array('http://www.good.go/', 'www.good.go', true),
            array('http://good.go/', 'www.good.go', false),
            array('http://also.good.go/', 'www.good.go', false),
        );
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

        $this->assertSame($expected, $actual, $message);
    }
}
