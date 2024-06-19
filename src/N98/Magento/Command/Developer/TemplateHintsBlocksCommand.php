<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractMagentoStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle template hints block command
 *
 * @package N98\Magento\Command\Developer
 */
class TemplateHintsBlocksCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:template-hints-blocks';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles template hints block names';

    /**
     * @var string
     */
    protected string $configPath = 'dev/debug/template_hints_blocks';

    /**
     * @var string
     */
    protected string $toggleComment = 'Template Hints Blocks';

    /**
     * @var string
     */
    protected string $scope = self::SCOPE_STORE_VIEW;

    /**
     * Add admin store to interactive prompt
     *
     * @var bool
     */
    protected bool $withAdminStore = true;

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
