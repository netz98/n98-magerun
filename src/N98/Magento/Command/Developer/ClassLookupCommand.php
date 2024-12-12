<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer;

use Mage;
use Mage_Core_Model_Config;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('dev:class:lookup')
            ->setDescription('Resolves a grouped class name')
            ->addArgument('type', InputArgument::REQUIRED, 'The type of the class (helper|block|model)')
            ->addArgument('name', InputArgument::REQUIRED, 'The grouped class name')
        ;
    }

    protected function _getConfig(): Mage_Core_Model_Config
    {
        return Mage::getConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $resolved = $this->_getConfig()->getGroupedClassName(
            $input->getArgument('type'),
            $input->getArgument('name'),
        );
        $output->writeln(
            ucfirst($input->getArgument('type')) . ' <comment>' . $input->getArgument('name') . '</comment> ' .
            'resolves to <comment>' . $resolved . '</comment>',
        );

        if (!class_exists('\\' . $resolved)) {
            $output->writeln('<info>Note:</info> Class <comment>' . $resolved . '</comment> does not exist!');
        }

        return Command::SUCCESS;
    }
}
