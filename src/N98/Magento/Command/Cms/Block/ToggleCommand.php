<?php

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Toggle CMS block command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ToggleCommand extends AbstractMagentoCommand
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('cms:block:toggle')
            ->addArgument('block_id', InputArgument::REQUIRED, 'Block ID or Identifier')
            ->setDescription('Toggle a cms block')
        ;
    }

    /**
     * Get an instance of cms/block
     *
     * @return \Mage_Cms_Model_Block
     */
    protected function _getBlockModel()
    {
        return $this->_getModel('cms/block', '\Mage_Cms_Model_Block');
    }

    /**
     * Execute the command
     *
     * @param InputInterface  $input
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
        $blockId = $input->getArgument('block_id');
        if (is_numeric($blockId)) {
            $block = $this->_getBlockModel()->load($blockId);
        } else {
            $block = $this->_getBlockModel()->load($blockId, 'identifier');
        }
        if (!$block->getId()) {
            return (int) $output->writeln('<error>Block was not found</error>');
        }
        $newStatus = !$block->getIsActive();
        $block
            ->setIsActive($newStatus)
            ->save();
        $output->writeln(sprintf(
            '<comment>Block</comment> <info>%s</info>',
            $newStatus ? 'enabled' : 'disabled'
        ));
        return 0;
    }
}
