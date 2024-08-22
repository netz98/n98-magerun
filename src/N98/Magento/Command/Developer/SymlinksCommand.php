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

    /**
     * @var string
     */
    protected $toggleComment = 'Symlinks';

    /**
     * @var string
     */
    protected $configPath = 'dev/template/allow_symlink';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;

    /**
     * @var string
     */
    protected $falseName = 'denied';

    /**
     * @var string
     */
    protected $trueName = 'allowed';

    /**
     * Add admin store to interactive prompt
     *
     * @var bool
     */
    protected $withAdminStore = true;
}
