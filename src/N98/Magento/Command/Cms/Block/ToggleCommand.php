<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use N98\Magento\Methods\Cms\Block;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Toggle CMS block command
 *
 * @package N98\Magento\Command\Cms\Block
 */
class ToggleCommand extends AbstractCmsBlockCommand
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Enable/disable CMS block';

    public const COMMAND_ARGUMENT_BLOCK_ID = 'block_id';

    /**
     * @var string
     */
    protected static $defaultName = 'cms:block:toggle';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Toggle a CMS block.';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument(
            self::COMMAND_ARGUMENT_BLOCK_ID,
            InputArgument::REQUIRED,
            'Block ID or Identifier'
        );
    }

    /**
     * {@inheritDoc}
     * @throws Throwable
     * @uses Block::getModel()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeSection($output, static::COMMAND_SECTION_TITLE_TEXT);

        /** @var string $blockId */
        $blockId = $input->getArgument(self::COMMAND_ARGUMENT_BLOCK_ID);
        $block = Block::getModel()->load($blockId, is_numeric($blockId) ? null : 'identifier');

        if (!$block->getId()) {
            $output->writeln('<error>Block was not found</error>');
            return Command::INVALID;
        }

        $block
            ->setIsActive((int)!$block->getIsActive())
            ->save();

        $output->writeln(sprintf(
            '<comment>Block "%s"</comment> <info>%s</info>',
            $block->getTitle(),
            $block->getIsActive() ? 'enabled' : 'disabled'
        ));

        return Command::SUCCESS;
    }
}
