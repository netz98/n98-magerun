<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Class PreCheckPhp
 * @package N98\Magento\Command\Installer\SubCommand
 */
class PreCheckPhp extends AbstractSubCommand
{
    /**
     * Check PHP environment against minimal required settings modules
     *
     * @return void
     */
    public function execute()
    {
        $this->checkExtensions();
        $this->checkXDebug();
    }

    /**
     * @return void
     */
    protected function checkExtensions()
    {
        $extensions = $this->commandConfig['installation']['pre-check']['php']['extensions'];
        $missingExtensions = [];
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        if (count($missingExtensions) > 0) {
            throw new RuntimeException(
                'The following PHP extensions are required to start installation: ' . implode(',', $missingExtensions)
            );
        }
    }

    /**
     * @throws \RuntimeException
     * @return void
     */
    protected function checkXDebug()
    {
        if (\extension_loaded('xdebug') &&
            function_exists('xdebug_is_enabled') &&
            \xdebug_is_enabled() &&
            ini_get('xdebug.max_nesting_level') != -1 &&
            \ini_get('xdebug.max_nesting_level') < 200
        ) {
            $errorMessage = 'Please change PHP ini setting "xdebug.max_nesting_level". '
                            . 'Please change it to a value >= 200. '
                            . 'Your current value is ' . \ini_get('xdebug.max_nesting_level');
            throw new RuntimeException($errorMessage);
        }
    }
}
