<?php

namespace N98\Magento\Application\Console\EventSubscriber;

use N98\Magento\Application\Console\Event;
use N98\Magento\Application\Console\Events;
use N98\Util\OperatingSystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckRootUser implements EventSubscriberInterface
{
    /**
     * @var string
     */
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
            Events::RUN_BEFORE => 'checkRunningAsRootUser',
        );
    }

    /**
     * Display a warning if a running n98-magerun as root user
     *
     * @param Event $event
     * @return void
     */
    public function checkRunningAsRootUser(Event $event)
    {
        if ($this->_isSkipRootCheck($event->getInput())) {
            return;
        }

        $config = $event->getApplication()->getConfig();
        if (!$config['application']['check-root-user']) {
            return;
        }

        if (OperatingSystem::isRoot()) {
            $output = $event->getOutput();
            $output->writeln(array(
                '',
                self::WARNING_ROOT_USER,
                '',
            ));
        }
    }

    /**
     * @return bool
     */
    protected function _isSkipRootCheck(InputInterface $input)
    {
        return $input->hasParameterOption('--skip-root-check');
    }
}
