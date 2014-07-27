<?php

namespace N98\Magento;

use N98\Util\OperatingSystem;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    const WARNING_ROOT_USER = '<error>It\'s not recommended to run n98-magerun as root user</error>';

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            'console.run.before' => 'checkRunningAsRootUser'
        );
    }

    /**
     * Display a warning if a running n98-magerun as root user
     *
     * @param ConsoleEvent $event
     * @return void
     */
    public function checkRunningAsRootUser(ConsoleEvent $event)
    {
        $output = $event->getOutput();
        if ($output instanceof ConsoleOutput) {
            $errorOutput = $output->getErrorOutput();
            if (OperatingSystem::isLinux() || OperatingSystem::isMacOs()) {
                if (function_exists('posix_getuid')) {
                    if (posix_getuid() === 0) {
                        $errorOutput->writeln('');
                        $errorOutput->writeln(self::WARNING_ROOT_USER);
                        $errorOutput->writeln('');
                    }
                }
            }
        }
    }
}
