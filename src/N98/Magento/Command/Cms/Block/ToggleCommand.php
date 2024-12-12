<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Mage_Cms_Model_Block;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('cms:block:toggle')
            ->addArgument('block_id', InputArgument::REQUIRED, 'Block ID or Identifier')
            ->setDescription('Toggle a cms block')
        ;
    }

    /**
     * Get an instance of cms/block
     */
    protected function _getBlockModel(): Mage_Cms_Model_Block
    {
        /** @var Mage_Cms_Model_Block $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('cms/block');
        return $mageCoreModelAbstract;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        /** @var string $blockId */
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
            ->setIsActive((int) $newStatus)
            ->save();
        $output->writeln(sprintf(
            '<comment>Block</comment> <info>%s</info>',
            $newStatus ? 'enabled' : 'disabled',
        ));

        return Command::SUCCESS;
    }
}
