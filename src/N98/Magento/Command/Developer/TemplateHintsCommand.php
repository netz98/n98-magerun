<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle template hints command
 *
 * @package N98\Magento\Command\Developer
 */
class TemplateHintsCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:template-hints';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggles template hints';

    /**
     * @var string
     */
    protected string $toggleComment = 'Template Hints';

    /**
     * @var string
     */
    protected string $configPath = 'dev/debug/template_hints';

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
