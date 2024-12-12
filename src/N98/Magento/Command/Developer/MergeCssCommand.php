<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle CSS merge command
 *
 * @package N98\Magento\Command\Developer
 */
class MergeCssCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:merge-css';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles CSS Merging';

    protected string $toggleComment = 'CSS Merging';

    protected string $configPath = 'dev/css/merge_css_files';

    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
