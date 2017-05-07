<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use DOMException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class XmlRendererTest
 *
 * @covers  N98\Util\Console\Helper\Table\Renderer\XmlRenderer
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class XmlRendererTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $renderer = new XmlRenderer();
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);

        $renderFactory = new RendererFactory();

        $renderer = $renderFactory->create('xml');
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);
    }

    /**
     * @return array
     * @see tableRendering
     */
    public function provideTables()
    {
        return array(
            array(
                array(
                    array(
                        "column" => "Doors wide > open",
                    ),
                    array(
                        "column" => "null \0 bytes FTW",
                    ),
                ),
                '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <headers>
    <header>column</header>
  </headers>
  <row>
    <column>Doors wide &gt; open</column>
  </row>
  <row>
    <column encoding="base64">bnVsbCAAIGJ5dGVzIEZUVw==</column>
  </row>
</table>',
            ),
            array(
                array(),
                '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <!--intentionally left blank, the table is empty-->
</table>',
            ),
            array(
                array(
                    array('Column1' => 'Value A1', 'Column2' => 'A2 is another value that there is'),
                    array(1, "multi\nline\nftw"),
                    array("C1 cell here!", new \SimpleXMLElement('<r>PHP Magic->toString() test</r>')),
                ),
                '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <headers>
    <header>Column1</header>
    <header>Column2</header>
  </headers>
  <row>
    <Column1>Value A1</Column1>
    <Column2>A2 is another value that there is</Column2>
  </row>
  <row>
    <Column1>1</Column1>
    <Column2>multi
line
ftw</Column2>
  </row>
  <row>
    <Column1>C1 cell here!</Column1>
    <Column2>PHP Magic-&gt;toString() test</Column2>
  </row>
</table>',
            ),
            array(
                array(array("\x00" => "foo")),
                '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <headers>
    <header></header>
  </headers>
  <row>
    <_>foo</_>
  </row>
</table>',
            ),
            array(
                array(
                    array("foo" => "bar"),
                    array("baz", "buz" => "here"),
                ),
                '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <headers>
    <header>foo</header>
  </headers>
  <row>
    <foo>bar</foo>
  </row>
  <row>
    <foo>baz</foo>
    <buz>here</buz>
  </row>
</table>',
            ),
        );
    }

    /**
     * @test
     * @expectedException DOMException
     * @expectedExceptionMessage Invalid name '0'
     */
    public function invalidName()
    {
        $renderer = new XmlRenderer();
        $output = new NullOutput();
        $renderer->render($output, array(array("foo")));
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage Encoding error, only US-ASCII and UTF-8 supported, can not process '
     */
    public function invalidEncoding()
    {
        $renderer = new XmlRenderer();
        $output = new NullOutput();
        $renderer->render($output, array(array("\xC1" => "foo")));
    }

    /**
     * @test
     * @dataProvider provideTables
     */
    public function tableRendering($rows, $expected)
    {
        $renderer = new XmlRenderer();
        $output = new StreamOutput(fopen('php://memory', 'w', false));

        $renderer->render($output, $rows);

        $this->assertEquals($expected . "\n", $this->getOutputBuffer($output));
    }
}
