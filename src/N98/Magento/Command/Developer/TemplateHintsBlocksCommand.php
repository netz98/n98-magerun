<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class TemplateHintsBlocksCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $commandName = 'dev:template-hints-blocks';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggles template hints block names';

    /**
     * @var string
     */
    protected $configPath = 'dev/debug/template_hints_blocks';

    /**
     * @var string
     */
    protected $toggleComment = 'Template Hints Blocks';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_STORE_VIEW;
}
