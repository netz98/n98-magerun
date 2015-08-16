<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class MergeCssCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:merge-css';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles CSS Merging';

    /**
     * @var string
     */
    protected $toggleComment = 'CSS Merging';

    /**
     * @var string
     */
    protected $configPath = 'dev/css/merge_css_files';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW_GLOBAL;
}
