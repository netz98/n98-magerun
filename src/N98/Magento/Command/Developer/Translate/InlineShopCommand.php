<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Translate;

use Mage_Core_Model_Store;
use N98\Magento\Command\AbstractStoreConfigCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Toggle inline translation command
 *
 * @package N98\Magento\Command\Developer\Translate
 */
class InlineShopCommand extends AbstractStoreConfigCommand
{
    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:translate:shop';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggle inline translation tool for shop';

    /**
     * @var string
     */
    protected string $configPath = 'dev/translate_inline/active';

    /**
     * @var string
     */
    protected string $toggleComment = 'Inline Translation';

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
