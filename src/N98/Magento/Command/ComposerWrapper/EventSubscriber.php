<?php

declare(strict_types=1);

namespace N98\Magento\Command\ComposerWrapper;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use N98\Magento\Application;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function chdir;
use function get_class;
use function strstr;

/**
 * Class EventSubscriber
 *
 * @package N98\Magento\Command\ComposerWrapper
 */
class EventSubscriber implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, string> The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents(): array
    {
        return ['console.command' => 'registerComposer'];
    }

    public function registerComposer(ConsoleEvent $consoleEvent): void
    {
        /*
         * Inject composer object in composer commands
         */
        $command = $consoleEvent->getCommand();
        if (strstr($command !== null ? get_class($command) : self::class, 'Composer\\Command\\')) {
            $consoleIO          = new ConsoleIO($consoleEvent->getInput(), $consoleEvent->getOutput(), $command->getHelperSet());
            /** @var Application $application */
            $application        = $command->getApplication();
            $magentoRootFolder  = $application->getMagentoRootFolder();
            $configFile         = $magentoRootFolder . '/composer.json';
            $composer           = Factory::create($consoleIO, $configFile);
            chdir($magentoRootFolder);
            $command->setComposer($composer);
            $command->setIO($consoleIO);
        }
    }
}
