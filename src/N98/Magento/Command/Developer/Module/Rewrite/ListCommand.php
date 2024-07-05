<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List rewrites command
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
class ListCommand extends AbstractRewriteCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Rewrites';

    protected const NO_DATA_MESSAGE = 'No rewrites were found.';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:module:rewrite:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all rewrites.';

    /**
     * {@inheritDoc}
     */
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['Type', 'Class', 'Rewrite'];
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $rewrites = array_merge($this->loadRewrites(), $this->loadAutoloaderRewrites());
            foreach ($rewrites as $type => $data) {
                if ((is_countable($data) ? count($data) : 0) > 0) {
                    foreach ($data as $class => $rewriteClass) {
                        $this->data[] = [
                            $type,
                            $class,
                            implode(', ', $rewriteClass)
                        ];
                    }
                }
            }
        }

        return $this->data;
    }
}
