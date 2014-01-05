<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class JsonRenderer implements RendererInterface
{
    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $rows
     */
    public function render(OutputInterface $output, array $rows)
    {
        $output->writeln(json_encode($rows, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
    }
}