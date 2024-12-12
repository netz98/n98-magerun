<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Observer;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List observer command
 *
 * @package N98\Magento\Command\Developer\Module\Observer
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:module:observer:list')
            ->addArgument('type', InputArgument::OPTIONAL, 'Observer type (global, admin, frontend, crontab)')
            ->setDescription('Lists all registered observers')
            ->addFormatOption()
            ->addOption(
                'sort',
                null,
                InputOption::VALUE_NONE,
                'Sort by event name ascending',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $type = $input->getArgument('type');

        $areas = ['global', 'adminhtml', 'frontend', 'crontab'];

        if ($type === null) {
            $type = $this->askForArrayEntry($areas, $input, $output, 'Please select an area:');
        }

        if (!in_array($type, $areas)) {
            throw new InvalidArgumentException('Invalid type! Use one of: ' . implode(', ', $areas));
        }

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Observers: ' . $type);
        }

        $frontendEvents = Mage::getConfig()->getNode($type . '/events');
        if (!$frontendEvents) {
            return Command::FAILURE;
        }

        $frontendEvents = $frontendEvents->asArray();
        if (true === $input->getOption('sort')) {
            // sorting for Observers is a bad idea because the order in which observers will be called is important.
            ksort($frontendEvents);
        }

        $table = [];
        foreach ($frontendEvents as $eventName => $eventData) {
            $observerList = [];
            foreach ($eventData['observers'] as $observer) {
                $observerList[] = $this->getObserver($observer, $type);
            }

            $table[] = [$eventName, implode("\n", $observerList)];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Event', 'Observers'])
            ->setRows($table)
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }

    /**
     * Get observer string (list entry)
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

        return str_pad($type, 11, ' ', STR_PAD_RIGHT);
    }
}
