<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TextRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class TextRenderer implements RendererInterface
{
    public function render(OutputInterface $output, array $rows): void
    {
        $table = new Table($output);
        $table->setStyle(new TableStyle());
        $table->setHeaders(array_keys($rows[0]));
        $table->setRows($rows);
        $table->render();
    }
}
