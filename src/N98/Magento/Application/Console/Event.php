<?php

namespace N98\Magento\Application\Console;

use N98\Magento\Application;
use Symfony\Component\EventDispatcher\Event as BaseEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event extends BaseEvent
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(Application $application, InputInterface $input, OutputInterface $output)
    {
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Gets the input instance.
     *
     * @return InputInterface An InputInterface instance
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Gets the output instance.
     *
     * @return OutputInterface An OutputInterface instance
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }
}
