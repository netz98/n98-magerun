<?php
/*
 * @author Tom Klingenberg <mot@fsfe.org>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class XmlRendererTest
 *
 * FIXME extract base testcase for renderer an push down output capturing helper methods
 *
 * @covers  N98\Util\Console\Helper\Table\Renderer\XmlRenderer
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class XmlRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $renderer = new XmlRenderer();
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);

        $renderFactory = new RendererFactory();

        $renderer = $renderFactory->create('text');
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);
    }

    /**
     * @test
     */
    public function rendering()
    {
        $renderer = new TextRenderer();
        $output   = new StreamOutput(fopen('php://memory', 'w', false));

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

    /**
     * @param $output
     *
     * @return string all output
     */
    private function getOutputBuffer(StreamOutput $output)
    {
        $handle = $output->getStream();

        rewind($handle);
        $display = stream_get_contents($handle);

        // Symfony2's StreamOutput has a hidden dependency on PHP_EOL which needs to be removed by
        // normalizing it to the standard newline for text here.
        $display = strtr($display, array(PHP_EOL => "\n"));

        return $display;
    }
}
