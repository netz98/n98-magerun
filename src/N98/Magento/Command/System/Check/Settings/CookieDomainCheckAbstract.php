<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\Result;

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
        $errorMessage = 'cookie-domain and ' . $this->class . ' base-URL do not match';

        if (strlen($cookieDomain)) {
            $isValid = $this->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

            $result->setStatus($isValid);

            if ($isValid) {
                $result->setMessage(
                    '<info>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain .
                    '</comment> of Store: <comment>' . $store->getCode() . '</comment> - OK</info>'
                );
            } else {
                $result->setMessage(
                    '<error>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain .
                    '</comment> of Store: <comment>' . $store->getCode() . '</comment> - ERROR: ' . $errorMessage .
                    '</error>'
                );
            }
        } else {
            $result->setMessage(
                '<info>Empty cookie Domain (' . $this->class . ') of Store: <comment>' . $store->getCode() .
                '</comment> - OK</info>'
            );
        }
    }

    /**
     * simplified cookie domain against base-URL validation
     *
     * it follows the following (incomplete) verification:
     *
     * - the site-domain is extracted from the base-url
     * - site-domain and cookie-domain are normalized by making them lowercase
     * - if the site-domain is empty, the check returns false because it's moot
     * - if the cookie-domain is smaller than three, the check returns false because it's moot
     * - if the cookie-domain does not start with a dot ("."), and the whole matches site-domain return true.
     * - otherwise the dot is removed and the cookie-domain is now with removed starting dot.
     * - the cookie domain must be the suffix of the site-domain and the remaining prefix of site-domain must end with
     *   a dot. returns true/false
     *
     * @param string $cookieDomain
     * @param string $siteUrl
     *
     * @return bool
     */
    public function validateCookieDomainAgainstUrl($cookieDomain, $siteUrl)
    {
        $siteDomain = strtolower(parse_url($siteUrl, PHP_URL_HOST));
        $siteLen = strlen($siteDomain);

        if (0 === $siteLen) {
            return false;
        }

        $cookieDomain = strtolower($cookieDomain);
        $cookieLen = strlen($cookieDomain);

        if (3 > $cookieLen) {
            return false;
        }

        $hasLeadingDot = $cookieDomain[0] === '.';
        if ($hasLeadingDot) {
            $cookieDomain = substr($cookieDomain, 1);
            $cookieLen = strlen($cookieDomain);
        } elseif ($siteDomain === $cookieDomain) {
            return true;
        }

        // cookie domain must at least contain a SLD.TLD, no match or match at offset 0 for '.' invalidates
        if (!strpos($cookieDomain, '.')) {
            return false;
        }

        $suffix = substr($siteDomain, -$cookieLen);

        if ($suffix !== $cookieDomain) {
            return false;
        }

        $prefix = substr($siteDomain, 0, -$cookieLen);
        if (0 === strlen($prefix)) {
            return false;
        }

        if (substr($prefix, -1) !== '.') {
            return false;
        }

        return true;
    }
}
