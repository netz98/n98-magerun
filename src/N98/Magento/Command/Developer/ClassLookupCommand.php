<?php

namespace N98\Magento\Command\Developer;

use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class lookup command
 *
 * @package N98\Magento\Command\Developer
 */
class ClassLookupCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:class:lookup')
            ->setDescription('Resolves a grouped class name')
            ->addArgument('type', InputArgument::REQUIRED, 'The type of the class (helper|block|model)')
            ->addArgument('name', InputArgument::REQUIRED, 'The grouped class name')
        ;
    }

    /**
     * @return \Mage_Core_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getConfig();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $resolved = $this->_getConfig()->getGroupedClassName(
            $input->getArgument('type'),
            $input->getArgument('name')
        );
        $output->writeln(
            ucfirst($input->getArgument('type')) . ' <comment>' . $input->getArgument('name') . '</comment> ' .
            'resolves to <comment>' . $resolved . '</comment>'
        );

        if (!class_exists('\\' . $resolved)) {
            $output->writeln('<info>Note:</info> Class <comment>' . $resolved . '</comment> does not exist!');
        }
        return 0;
    }
}
