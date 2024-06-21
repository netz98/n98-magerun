<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle JS merging command
 *
 * @package N98\Magento\Command\Developer
 */
class MergeJsCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:merge-js';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles JS Merging';

    /**
     * @var string
     */
    protected string $toggleComment = 'JS Merging';

    /**
     * @var string
     */
    protected string $configPath = 'dev/js/merge_files';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
