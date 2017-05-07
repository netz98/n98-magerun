<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TextRenderer
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class TextRenderer implements RendererInterface
{
    /**
     * @param OutputInterface $output
     * @param array $rows headers are expected to be the keys of the first row.
     * @return void
     */
    public function render(OutputInterface $output, array $rows)
    {
        $table = new Table($output);
        $table->setStyle(new TableStyle());
        $table->setHeaders(array_keys($rows[0]));
        $table->setRows($rows);
        $table->render();
    }
}
