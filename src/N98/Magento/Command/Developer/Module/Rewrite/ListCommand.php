<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use N98\Magento\Command\CommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List rewrites command
 *
 * @package N98\Magento\Command\Developer\Module
 */
class ListCommand extends AbstractRewriteCommand implements CommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'Rewrites';

    protected const NO_DATA_MESSAGE = 'No rewrites were found.';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:module:rewrite:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all rewrites.';

    /**
     * {@inheritdoc}
     * @return array<int, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $rewrites = array_merge($this->loadRewrites(), $this->loadAutoloaderRewrites());
            foreach ($rewrites as $type => $data) {
                if ((is_countable($data) ? count($data) : 0) > 0) {
                    foreach ($data as $class => $rewriteClass) {
                        $this->data[] = [
                            'Type' => $type,
                            'Class' => $class,
                            'Rewrite' => implode(', ', $rewriteClass)
                        ];
                    }
                }
            }
        }

        return $this->data;
    }
}
