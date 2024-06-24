<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractStoreConfigCommand;

/**
 * Toggle CSS merging command
 *
 * @package N98\Magento\Command\Developer
 */
class MergeCssCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:merge-css';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles CSS merging';

    /**
     * @var string
     */
    protected string $toggleComment = 'CSS Merging';

    /**
     * @var string
     */
    protected string $configPath = 'dev/css/merge_css_files';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
