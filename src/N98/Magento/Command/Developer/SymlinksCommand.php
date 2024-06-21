<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle allow symlinks setting command
 *
 * @package N98\Magento\Command\Developer
 */
class SymlinksCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:symlinks';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles allow symlinks setting';

    /**
     * @var string
     */
    protected string $toggleComment = 'Symlinks';

    /**
     * @var string
     */
    protected string $configPath = 'dev/template/allow_symlink';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;

    /**
     * @var string
     */
    protected string $falseName = 'denied';

    /**
     * @var string
     */
    protected string $trueName = 'allowed';

    /**
     * Add admin store to interactive prompt
     *
     * @var bool
     */
    protected bool $withAdminStore = true;
}
