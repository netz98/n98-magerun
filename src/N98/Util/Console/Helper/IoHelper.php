<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class IoHelper
 *
 * Helper named "io" providing (Input and) OutputInterface within the global helper-set
 *
 * Register itself on @see ConsoleEvents::COMMAND event to populate helper fields
 *
 * @package N98\Util\Console\Helper
 */
class IoHelper implements HelperInterface, EventSubscriberInterface
{
    /**
     * @var HelperSet
     */
    private $helperSet;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @see getSubscribedEvents
     *
     * @param ConsoleCommandEvent $event
     */
    public function initializeEventIo(ConsoleCommandEvent $event)
    {
        /** @var  $helper IoHelper */
        $helper = $event->getCommand()->getHelperSet()->get($this->getName());
        $helper->initializeIo($event->getInput(), $event->getOutput());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function initializeIo(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /*
     * HelperInterface
     */

    /**
     * Sets the helper set associated with this helper.
     *
     * @param HelperSet $helperSet A HelperSet instance
     *
     * @api
     */
    public function setHelperSet(HelperSet $helperSet = null)
    {
        $this->helperSet = $helperSet;
    }

    /**
     * Gets the helper set associated with this helper.
     *
     * @return HelperSet A HelperSet instance
     *
     * @api
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'io';
    }

    /*
     * EventSubscriberInterface
     */

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::COMMAND => 'initializeEventIo', /** @see initializeEventIo */
        );
    }
}

