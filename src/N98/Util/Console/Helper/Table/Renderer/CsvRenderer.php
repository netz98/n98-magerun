<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class CsvRenderer implements RendererInterface
{
    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $rows
     */
    public function render(OutputInterface $output, array $rows)
    {
        if ($output instanceof ConsoleOutput) {
            $stream = $output->getStream();
        } else {
            $stream = fopen('php://stdout', 'w');
        }

        $i = 0;
        foreach ($rows as $row) {
            if ($i++ == 0) {
                fputcsv($stream, array_keys($row));
            }
            fputcsv($stream, $row);
        }
    }
}