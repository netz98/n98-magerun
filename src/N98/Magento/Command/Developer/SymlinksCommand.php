<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle symlinks command
 *
 * @package N98\Magento\Command\Developer
 */
class SymlinksCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:symlinks';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggle allow symlinks setting';

    protected string $toggleComment = 'Symlinks';

    protected string $configPath = 'dev/template/allow_symlink';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;

    protected string $falseName = 'denied';

    protected string $trueName = 'allowed';

    protected bool $withAdminStore = true;
}
