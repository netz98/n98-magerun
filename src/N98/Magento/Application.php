<?php

namespace N98\Magento;

use Symfony\Component\Console\Application as BaseApplication;
use N98\Magento\Command\LocalConfig\GenerateCommand as GenerateLocalXmlConfigCommand;
use N98\Magento\Command\Database\DumpCommand as DumpDatabaseCommand;
use N98\Magento\Command\Database\InfoCommand as DatabaseInfoCommand;

class Application extends BaseApplication
{
    /**
     * @var string
     */
    const APP_NAME = 'n98-magerun';

    /**
     * @var string
     */
    const APP_VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::APP_NAME, self::APP_VERSION);
        $this->add(new GenerateLocalXmlConfigCommand());
        $this->add(new DumpDatabaseCommand());
        $this->add(new DatabaseInfoCommand());
    }

}