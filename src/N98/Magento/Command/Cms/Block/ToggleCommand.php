<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cms\Block;

use Symfony\Component\Console\Attribute\AsCommand;
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
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cms:block:toggle';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Toggle a CMS block.';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument(
            self::COMMAND_ARGUMENT_BLOCK_ID,
            InputArgument::REQUIRED,
            'Block ID or Identifier'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeSection($output, static::COMMAND_SECTION_TITLE_TEXT);

        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        $blockId = $input->getArgument(self::COMMAND_ARGUMENT_BLOCK_ID);
        $block = $this->_getBlockModel()->load($blockId, is_numeric($blockId) ? null : 'identifier');

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
