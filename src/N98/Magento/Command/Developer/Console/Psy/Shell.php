<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Console\Psy;

use Psy\Configuration;
use Psy\Shell as BaseShell;

/**
 * Shell command
 *
 * @package N98\Magento\Command\Developer\Console\Psy
 */
class Shell extends BaseShell
{
    public function __construct(?Configuration $configuration = null)
    {
        parent::__construct($configuration);

        $this->addCommands($this->getDefaultCommands());
    }
}
