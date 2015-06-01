<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class SymlinksCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:symlinks';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggle allow symlinks setting';

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
