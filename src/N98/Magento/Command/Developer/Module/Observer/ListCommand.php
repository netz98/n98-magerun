<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Observer;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List observer command
 *
 * @package N98\Magento\Command\Developer\Module\Observer
 */
class ListCommand extends AbstractMagentoCommand implements AbstractMagentoCommandFormatInterface
{
    public const COMMAND_ARGUMENT_TYPE = 'type';

    public const COMMAND_OPTION_SORT = 'sort';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:module:observer:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all registered observers.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_TYPE,
                InputArgument::OPTIONAL,
                'Observer type (global, admin, frontend, crontab)'
            )
            ->addOption(
                self::COMMAND_OPTION_SORT,
                null,
                InputOption::VALUE_NONE,
                'Sort by event name ascending'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            /** @var string $type */
            $type = $input->getArgument(self::COMMAND_ARGUMENT_TYPE);

            $areas = ['global', 'adminhtml', 'frontend', 'crontab'];

            if ($type === null) {
                /** @var string $type */
                $type = $this->askForArrayEntry($areas, $input, $output, 'Please select an area:');
            }

            if (!in_array($type, $areas)) {
                throw new InvalidArgumentException('Invalid type! Use one of: ' . implode(', ', $areas));
            }

            if ($input->getOption(parent::COMMAND_OPTION_FORMAT) === null) {
                $this->writeSection($output, 'Observers: ' . $type);
            }

            $frontendEvents = $this->_getMageConfigNode($type . '/events')->asArray();
            if (true === $input->getOption(self::COMMAND_OPTION_SORT)) {
                // sorting for Observers is a bad idea because the order in which observers will be called is important.
                ksort($frontendEvents);
            }

            foreach ($frontendEvents as $eventName => $eventData) {
                $observerList = [];
                foreach ($eventData['observers'] as $observer) {
                    $observerList[] = $this->getObserver($observer, $type);
                }
                $this->data[] = [
                    'Event' => $eventName,
                    'Observers' => implode("\n", $observerList)
                ];
            }
        }

        return $this->data;
    }

    /**
     * Get observer string (list entry)
     *
     * @param array<string, string> $observer
     * @param string $area
     * @return string
     */
    protected function getObserver(array $observer, string $area): string
    {
        $type = $this->getObserverType($observer, $area);

        $class = '';
        if (isset($observer['class'])) {
            $class = $observer['class'];
        } elseif (isset($observer['model'])) {
            $class = $observer['model'];
        }

        $method = isset($observer['method']) ? '::' . $observer['method'] : '';

        return $type . $class . $method;
    }

    /**
     * @param array<string, string> $observer
     * @param string $area
     * @return string
     */
    private function getObserverType(array $observer, string $area): string
    {
        // singleton is the default type Mage_Core_Model_App::dispatchEvent
        $type = 'singleton';
        if ('crontab' === $area) {
            // direct model name is the default type Mage_Core_Model_Config::loadEventObservers in crontab area
            // '' means that no Mage::get___() will be used
            $type = '';
        }
        if (isset($observer['type'])) {
            $type = $observer['type'];
        }

        return str_pad($type, 11);
    }
}
