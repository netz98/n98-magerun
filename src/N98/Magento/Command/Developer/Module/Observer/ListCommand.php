<?php

namespace N98\Magento\Command\Developer\Module\Observer;

use InvalidArgumentException;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:module:observer:list')
            ->addArgument('type', InputArgument::OPTIONAL, 'Observer type (global, admin, frontend, crontab)')
            ->setDescription('Lists all registered observers')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->addOption(
                'sort',
                null,
                InputOption::VALUE_NONE,
                'Sort by event name ascending'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws InvalidArgumentException
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $type = $input->getArgument('type');

        $areas = array(
            'global',
            'adminhtml',
            'frontend',
            'crontab',
        );

        if ($type === null) {
            $type = $this->askForArrayEntry($areas, $output, 'Please select an area:');
        }

        if (!in_array($type, $areas)) {
            throw new InvalidArgumentException('Invalid type! Use one of: ' . implode(', ', $areas));
        }

        if ($input->getOption('format') === null) {
            $this->writeSection($output, 'Observers: ' . $type);
        }
        $frontendEvents = \Mage::getConfig()->getNode($type . '/events')->asArray();
        if (true === $input->getOption('sort')) {
            // sorting for Observers is a bad idea because the order in which observers will be called is important.
            ksort($frontendEvents);
        }
        $table = array();
        foreach ($frontendEvents as $eventName => $eventData) {
            $observerList = array();
            foreach ($eventData['observers'] as $observer) {
                $observerList[] = $this->getObserver($observer, $type);
            }
            $table[] = array(
                $eventName,
                implode("\n", $observerList),
            );
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(array('Event', 'Observers'))
            ->setRows($table)
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * get observer string (list entry)
     *
     * @param array  $observer
     * @param string $area
     *
     * @return string
     */
    protected function getObserver(array $observer, $area)
    {
        $type = $this->getObserverType($observer, $area);

        $class = '';
        if (isset($observer['class'])) {
            $class = $observer['class'];
        } elseif (isset($observer['model'])) {
            $class = $observer['model'];
        }

        $method = isset($observer['method']) ? '::' . $observer['method'] : '';

        $observer = $type . $class . $method;

        return $observer;
    }

    /**
     * @param array  $observer
     * @param string $area
     *
     * @return string
     */
    private function getObserverType(array $observer, $area)
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
        $type = str_pad($type, 11, ' ', STR_PAD_RIGHT);

        return $type;
    }
}
