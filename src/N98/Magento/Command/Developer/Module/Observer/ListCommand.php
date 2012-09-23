<?php

namespace N98\Magento\Command\Developer\Module\Observer;

use N98\Magento\Command\AbstractMagentoCommand;
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
            ->addArgument('type', InputArgument::OPTIONAL, 'Observer type (global, admin, frontend)')
            ->setDescription('Lists all registered observers')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {
            $type = $input->getArgument('type');

            $areas = array(
                'global',
                'adminhtml',
                'frontend',
            );

            if ($type === null) {
                foreach ($areas as $key => $area) {
                    $question[] = '<comment>[' . ($key + 1) . ']</comment> ' . $area . "\n";
                }
                $question[] = '<question>Please select a area:</question>';

                $type = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($areas) {
                    if (!in_array($typeInput, range(1, count($areas)))) {
                        throw new \InvalidArgumentException('Invalid area');
                    }
                    return $areas[$typeInput - 1];
                });
            }

            if (!in_array($type, $areas)) {
                throw new \InvalidArgumentException('Invalid type! Use one of: ' . implode(', ', $areas));
            }

            $this->writeSection($output, 'Observers: ' . $type);
            $frontendEvents = \Mage::getConfig()->getNode($type . '/events')->asArray();
            $table = new \Zend_Text_Table(array('columnWidths' => array(80, 80)));
            foreach ($frontendEvents as $eventName => $eventData) {
                $observerList = array();
                foreach ($eventData['observers'] as $observer) {
                    $observerList[] = $observer['class'] . (isset($observer['method']) ? '::' . $observer['method'] : '');
                }
                $table->appendRow(array(
                    $eventName,
                    implode("\n", $observerList),
                ));
            }

            $output->write($table->render());
        }
    }
}