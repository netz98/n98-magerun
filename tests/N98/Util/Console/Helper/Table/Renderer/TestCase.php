<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class RendererTestCase
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * helper method to get output as string out of a StreamOutput
     *
     * @param $output
     *
     * @return string all output
     */
    protected function getOutputBuffer(StreamOutput $streamOutput)
    {
        $stream = $streamOutput->getStream();

        rewind($stream);
        $display = stream_get_contents($stream);

        // Symfony2's StreamOutput has a hidden dependency on PHP_EOL which needs to be removed by
        // normalizing it to the standard newline for text here.
        $display = strtr($display, [PHP_EOL => "\n"]);

        return $display;
    }
}
