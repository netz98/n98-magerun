<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Settings;

/**
 * Class UnsecureCookieDomainCheck
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
class UnsecureCookieDomainCheck extends CookieDomainCheckAbstract
{
    protected string $class = 'unsecure';
}
