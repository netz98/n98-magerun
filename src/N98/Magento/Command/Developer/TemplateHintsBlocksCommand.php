<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

/**
 * Toggle template blocks hints command
 *
 * @package N98\Magento\Command\Developer
 */
class TemplateHintsBlocksCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:template-hints-blocks';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggles template hints block names';

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

    /**
     * Add admin store to interactive prompt
     *
     * @var bool
     */
    protected $withAdminStore = true;

    /**
     * If required, handle the output and possible change of the developer IP restrictions
     *
     * @param Mage_Core_Model_Store $store
     * @param bool $disabled
     */
    protected function _afterSave(Mage_Core_Model_Store $store, bool $disabled): void
    {
        $this->detectAskAndSetDeveloperIp($store, $disabled);
    }
}
