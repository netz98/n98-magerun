<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class CsvRenderer implements RendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function render(OutputInterface $output, array $rows)
    {
        // no rows - there is nothing to do
        if (!$rows) {
            return;
        }

        if ($output instanceof StreamOutput) {
            $stream = $output->getStream();
        } else {
            $stream = \STDOUT;
        }

        fputcsv($stream, array_keys(reset($rows)));
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
    }
}
