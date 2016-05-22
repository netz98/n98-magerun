<?php

namespace N98\Magento\Command\Developer\Console\Psy;

use Psy\Configuration;
use Psy\Shell as BaseShell;

class Shell extends BaseShell
{
    public function __construct(Configuration $config = null)
    {
        parent::__construct($config);

        $this->addCommands($this->getDefaultCommands());
    }
}
