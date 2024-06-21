<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Console\Psy;

use Psy\Configuration;
use Psy\Shell as BaseShell;

/**
 * Console Psy shell
 *
 * @package N98\Magento\Command\Developer\Console\Psy
 */
class Shell extends BaseShell
{
    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);

        $this->addCommands($this->getDefaultCommands());
    }
}
