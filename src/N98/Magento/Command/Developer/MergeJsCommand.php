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

    protected string $toggleComment = 'JS Merging';

    protected string $configPath = 'dev/js/merge_files';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
