<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class CsvRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class CsvRenderer implements RendererInterface
{
    public function render(OutputInterface $output, array $rows): void
    {
        // no rows - there is nothing to do
        if ($rows === []) {
            return;
        }

        $stream = $output instanceof StreamOutput ? $output->getStream() : \STDOUT;

        fputcsv($stream, array_keys(reset($rows)));
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
    }
}
