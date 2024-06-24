<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

/**
 * Enable module(s) command
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
class EnableCommand extends AbstractDisableenableCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:enable';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Enables a module or all modules in codePool.';

    /**
     * @var string
     */
    protected string $commandName = 'enable';
}
