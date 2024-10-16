<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface RendererInterface
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
interface RendererInterface
{
    /**
     * @param OutputInterface $output
     * @param array $rows headers are expected to be the keys of the first row.
     * @return void
     */
    public function render(OutputInterface $output, array $rows);
}
