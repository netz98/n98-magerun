<?php

declare(strict_types=1);

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
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class IoHelper implements HelperInterface, EventSubscriberInterface
{
    public const HELPER_NAME = 'io';

    private HelperSet $helperSet;

    private ?OutputInterface $output = null;

    private ?InputInterface $input = null;

    /**
     * @see getSubscribedEvents
     */
    public function initializeEventIo(ConsoleCommandEvent $consoleCommandEvent): void
    {
        $set = $consoleCommandEvent->getCommand()->getHelperSet();
        if (!$set->has(self::HELPER_NAME)) {
            return;
        }

        /** @var IoHelper $helper */
        $helper = $set->get(self::HELPER_NAME);
        $helper->initializeIo($consoleCommandEvent->getInput(), $consoleCommandEvent->getOutput());
    }

    public function initializeIo(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /*
     * HelperInterface
     */

    /**
     * Sets the helper set associated with this helper.
     *
     * @param HelperSet|null $helperSet A HelperSet instance
     *
     * @api
     */
    public function setHelperSet(?HelperSet $helperSet = null): void
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
    public function getHelperSet(): HelperSet
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
    public function getName(): string
    {
        return self::HELPER_NAME;
    }

    /*
     * EventSubscriberInterface
     */
    /**
     * @inheritdoc
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => 'initializeEventIo'];
    }
}
