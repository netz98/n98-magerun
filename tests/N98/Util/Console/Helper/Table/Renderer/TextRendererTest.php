<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use SimpleXMLElement;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class TextRendererTest
 *
 * @covers  N98\Util\Console\Helper\Table\Renderer\TextRenderer
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class TextRendererTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $renderer = new TextRenderer();
        self::assertInstanceOf(__NAMESPACE__ . '\\TextRenderer', $renderer);

        $rendererFactory = new RendererFactory();

        $renderer = $rendererFactory->create('text');
        self::assertInstanceOf(__NAMESPACE__ . '\\TextRenderer', $renderer);
    }

    /**
     * @test
     */
    public function rendering()
    {
        $textRenderer = new TextRenderer();
        $streamOutput = new StreamOutput(fopen('php://memory', 'wb', false));

        $rows = [['Column1' => 'Value A1', 'Column2' => 'A2 is another value that there is'], [1, "multi\nline\nftw"], ['C1 cell here!', new SimpleXMLElement('<r>PHP Magic->toString() test</r>')]];

        $expected = '+---------------+-----------------------------------+
| Column1       | Column2                           |
+---------------+-----------------------------------+
| Value A1      | A2 is another value that there is |
| 1             | multi                             |
|               | line                              |
|               | ftw                               |
| C1 cell here! | PHP Magic->toString() test        |
+---------------+-----------------------------------+' . "\n";

        $textRenderer->render($streamOutput, $rows);

        self::assertEquals($expected, $this->getOutputBuffer($streamOutput));
    }
}
