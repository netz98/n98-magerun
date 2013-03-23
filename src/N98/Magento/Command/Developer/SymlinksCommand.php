<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

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
    protected $falseName = 'allowed';

    /**
     * @var string
     */
    protected $trueName = 'denied';
}