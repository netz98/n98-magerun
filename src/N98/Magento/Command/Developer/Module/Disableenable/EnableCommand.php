<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Disableenable;

use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Enable module(s) command
 *
 * @package N98\Magento\Command\Developer\Module\Disableenable
 */
class EnableCommand extends AbstractCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:module:enable';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Enables a module or all modules in codePool.';
}
