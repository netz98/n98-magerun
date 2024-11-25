<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Settings;

/**
 * Class UnsecureBaseUrlCheck
 *
 * @package N98\Magento\Command\System\Check\Settings
 */
class UnsecureBaseUrlCheck extends BaseUrlCheckAbstract
{
    protected string $class = 'unsecure';
}
