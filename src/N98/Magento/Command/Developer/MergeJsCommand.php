<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class MergeJsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:merge-js';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles JS Merging';

    /**
     * @var string
     */
    protected $toggleComment = 'JS Merging';

    /**
     * @var string
     */
    protected $configPath = 'dev/js/merge_files';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
