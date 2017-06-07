<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

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
        $this->assertInstanceOf(__NAMESPACE__ . '\\TextRenderer', $renderer);

        $renderFactory = new RendererFactory();

        $renderer = $renderFactory->create('text');
        $this->assertInstanceOf(__NAMESPACE__ . '\\TextRenderer', $renderer);
    }

    /**
     * @test
     */
    public function rendering()
    {
        $renderer = new TextRenderer();
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $rows = array(
            array('Column1' => 'Value A1', 'Column2' => 'A2 is another value that there is'),
            array(1, "multi\nline\nftw"),
            array("C1 cell here!", new \SimpleXMLElement('<r>PHP Magic->toString() test</r>')),
        );

        $expected = '+---------------+-----------------------------------+
| Column1       | Column2                           |
+---------------+-----------------------------------+
| Value A1      | A2 is another value that there is |
| 1             | multi                             |
|               | line                              |
|               | ftw                               |
| C1 cell here! | PHP Magic->toString() test        |
+---------------+-----------------------------------+' . "\n";

        $renderer->render($output, $rows);

        $this->assertEquals($expected, $this->getOutputBuffer($output));
    }
}
