<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class JsonRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class JsonRenderer implements RendererInterface
{
    public function render(OutputInterface $output, array $rows): void
    {
        $options = JSON_FORCE_OBJECT;
        $options |= JSON_PRETTY_PRINT;

        $out = json_encode($rows, $options);
        if ($out) {
            $output->writeln($out);
        }
    }
}
