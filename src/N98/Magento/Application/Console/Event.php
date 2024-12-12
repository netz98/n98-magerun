<?php

declare(strict_types=1);

namespace N98\Magento\Application\Console;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    protected Application $application;

    protected InputInterface $input;

    protected OutputInterface $output;

    /**
     * @var EventDispatcherInterface Dispatcher that dispatched this event
     */
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(Application $application, InputInterface $input, OutputInterface $output)
    {
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Gets the input instance.
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * Gets the output instance.
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * Stores the EventDispatcher that dispatches this Event.
     *
     * @deprecated since version 2.4, to be removed in 3.0. The event dispatcher is passed to the listener call.
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns the EventDispatcher that dispatches this Event.
     *
     *
     * @deprecated since version 2.4, to be removed in 3.0. The event dispatcher is passed to the listener call.
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
