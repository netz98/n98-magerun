<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\StoreCheck;

/**
 * Class CookieDomainCheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
abstract class CookieDomainCheckAbstract extends CheckAbstract
{
    protected $class = 'abstract';

    public function initConfigPaths()
    {
        $this->registerStoreConfigPath('baseUrl', 'web/' . $this->class . '/base_url');
        $this->registerStoreConfigPath('cookieDomain', 'web/cookie/cookie_domain');
    }

    /**
     * @param Result                 $result
     * @param \Mage_Core_Model_Store $store
     * @param string                 $baseUrl      setting
     * @param string                 $cookieDomain setting
     */
    protected function checkSettings(Result $result, \Mage_Core_Model_Store $store, $baseUrl, $cookieDomain)
    {
        $errorMessage = 'Cookie Domain and ' . ucfirst($this->class) . ' BaseURL (http) does not match';

        if (strlen($cookieDomain)) {
            $isValid = $this->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

            $result->setStatus($isValid);

            if ($isValid) {
                $result->setMessage('<info>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain . '</comment> of Store: <comment>' . $store->getCode() . '</comment> - OK</info>');
            } else {
                $result->setMessage('<error>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain . '</comment> of Store: <comment>' . $store->getCode() . '</comment> - ERROR: ' . $errorMessage . '</error>');
            }
        } else {
            $result->setMessage('<info>Empty cookie Domain (' . $this->class . ') of Store: <comment>' . $store->getCode() . '</comment> - OK</info>');
        }
    }

    /**
     * quite rough cookie domain against base-URL validation
     *
     * follows RFC6265 Domain Matching <https://tools.ietf.org/html/rfc6265#section-5.1.3>
     *
     * @param string $cookieDomain
     * @param string $url
     *
     * @return bool
     */
    private function validateCookieDomainAgainstUrl($cookieDomain, $url)
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST));

        $hostLen = strlen($host);
        if (!$hostLen) {
            return false;
        }

        $domain = strtolower($cookieDomain);

        // Let cookie-domain be the attribute-value without the leading %x2E (".") character
        // see 5.2.3. The Domain Attribute <https://tools.ietf.org/html/rfc6265#section-5.2.3>
        if (strlen($domain) && ($domain[0] === '.')) {
            $domain = substr($domain, 1);
        }

        $domainLen = strlen($domain);

        if (!$domainLen) {
            return false;
        }

        return (
            ($host === $domain)
            || (
                ($hostLen > $domainLen)
                && (substr($host, -$domainLen) === $domain)
                && (substr($host, -$domainLen - 1, 1) === '.')
                && (ip2long($host) === false)
            )
        );
    }
}
