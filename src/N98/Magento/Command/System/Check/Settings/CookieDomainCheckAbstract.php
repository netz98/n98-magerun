<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\StoreCheck;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;

abstract class CookieDomainCheckAbstract implements StoreCheck
{
    protected $class = 'abstract';

    final public function __construct()
    {
        $whitelist = array('secure' => 1, 'unsecure' => 2);

        if (!isset($whitelist[$this->class])) {
            throw new \LogicException(
                sprintf('cookie class "%s" (%s::$class) is invalid.', $this->class, get_class($this))
            );
        }
    }

    /**
     * @param ResultCollection       $results
     * @param \Mage_Core_Model_Store $store
     */
    final public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result       = $results->createResult();
        $errorMessage = 'Cookie Domain and ' . ucfirst($this->class) . ' BaseURL (http) does not match';

        $baseUrl      = \Mage::getStoreConfig('web/' . $this->class . '/base_url', $store);
        $cookieDomain = \Mage::getStoreConfig('web/cookie/cookie_domain', $store);

        if (strlen($cookieDomain)) {
            $isValid = $this->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

            $result->setStatus($isValid ? Result::STATUS_OK : Result::STATUS_ERROR);

            if ($result->isValid()) {
                $result->setMessage('<info>Cookie Domain (' . $this->class . ') of Store: <comment>' . $store->getCode() . '</comment> OK</info>');
            } else {
                $result->setMessage('<error>Cookie Domain (' . $this->class . ') of Store: <comment>' . $store->getCode() . '</comment> ' . $errorMessage . '</error>');
            }
        } else {
            $result->setMessage('<info>Cookie Domain (' . $this->class . ') of Store: <comment>' . $store->getCode() . '</comment> OK - No domain set</info>');
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
        strlen($domain) && ($domain[0] === '.') && ($domain = substr($domain, 1));

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
