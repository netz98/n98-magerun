<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

/**
 * Disable module(s) command
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
class DisableCommand extends AbstractDisableenableCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:disable';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Disables a module or all modules in codePool.';

    /**
     * @var string
     */
    protected string $commandName = 'enable';
}
