<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle JS merge command
 *
 * @package N98\Magento\Command\Developer
 */
class MergeJsCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:merge-js';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles JS Merging';

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
