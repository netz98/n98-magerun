<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Settings;

/**
 * Class SecureCookieDomainCheck
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
class SecureCookieDomainCheck extends CookieDomainCheckAbstract
{
    protected string $class = 'secure';
}
