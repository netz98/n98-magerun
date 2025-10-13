<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Settings;

use Mage_Core_Model_Store;
use N98\Magento\Command\System\Check\Result;

/**
 * Class CookieDomainCheckAbstract
 *
 * @package N98\Magento\Command\System\Check\Settings
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
abstract class CookieDomainCheckAbstract extends CheckAbstract
{
    protected string $class = 'abstract';

    protected function initConfigPaths(): void
    {
        $this->registerStoreConfigPath('baseUrl', 'web/' . $this->class . '/base_url');
        $this->registerStoreConfigPath('cookieDomain', 'web/cookie/cookie_domain');
    }

    protected function checkSettings(Result $result, ?Mage_Core_Model_Store $mageCoreModelStore, string $baseUrl, ?string $cookieDomain): void
    {
        $errorMessage   = 'cookie-domain and ' . $this->class . ' base-URL do not match';
        $websiteCode    = $mageCoreModelStore instanceof Mage_Core_Model_Store ? $mageCoreModelStore->getCode() : '';

        if ($cookieDomain && strlen($cookieDomain) !== 0) {
            $isValid = $this->validateCookieDomainAgainstUrl($cookieDomain, $baseUrl);

            $result->setStatus($isValid);

            if ($isValid) {
                $result->setMessage(
                    '<info>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain .
                    '</comment> of Store: <comment>' . $websiteCode . '</comment> - OK</info>',
                );
            } else {
                $result->setMessage(
                    '<error>Cookie Domain (' . $this->class . '): <comment>' . $cookieDomain .
                    '</comment> of Store: <comment>' . $websiteCode . '</comment> - ERROR: ' . $errorMessage .
                    '</error>',
                );
            }
        } else {
            $result->setMessage(
                '<info>Empty cookie Domain (' . $this->class . ') of Store: <comment>' . $websiteCode .
                '</comment> - OK</info>',
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
     */
    public function validateCookieDomainAgainstUrl(string $cookieDomain, string $siteUrl): bool
    {
        $host       = parse_url($siteUrl, PHP_URL_HOST);
        $siteDomain = strtolower((string) $host);
        $siteLen    = strlen($siteDomain);

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
            $cookieDomain = (string) substr($cookieDomain, 1);
            $cookieLen    = strlen($cookieDomain);
        } elseif ($siteDomain === $cookieDomain) {
            return true;
        }

        // cookie domain must at least contain a SLD.TLD, no match or match at offset 0 for '.' invalidates
        if (in_array(strpos($cookieDomain, '.'), [0, false], true)) {
            return false;
        }

        $suffix = substr($siteDomain, -$cookieLen);
        if ($suffix !== $cookieDomain) {
            return false;
        }

        $prefix = substr($siteDomain, 0, -$cookieLen);
        if (in_array($prefix, [false, '', '0'], true)) {
            return false;
        }

        return substr($prefix, -1) === '.';
    }
}
