<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Util\Console\Helper\Table\Renderer;

use SimpleXMLElement;
use DOMException;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class XmlRendererTest
 *
 * @covers  N98\Util\Console\Helper\Table\Renderer\XmlRenderer
 * @package N98\Util\Console\Helper\Table\Renderer
 */
final class XmlRendererTest extends TestCase
{
    public function testCreation()
    {
        $renderer = new XmlRenderer();
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);

        $rendererFactory = new RendererFactory();

        $renderer = $rendererFactory->create('xml');
        $this->assertInstanceOf(__NAMESPACE__ . '\\XmlRenderer', $renderer);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     * @see tableRendering
     */
    public function provideTables(): \Iterator
    {
        yield [[['column' => 'Doors wide > open'], ['column' => "null \0 bytes FTW"]], '<?xml version="1.0" encoding="UTF-8"?>
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
</table>'];
        yield [[], '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <!--intentionally left blank, the table is empty-->
</table>'];
        yield [[['Column1' => 'Value A1', 'Column2' => 'A2 is another value that there is'], [1, "multi\nline\nftw"], ['C1 cell here!', new SimpleXMLElement('<r>PHP Magic->toString() test</r>')]], '<?xml version="1.0" encoding="UTF-8"?>
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
</table>'];
        yield [[["\x00" => 'foo']], '<?xml version="1.0" encoding="UTF-8"?>
<table>
  <headers>
    <header></header>
  </headers>
  <row>
    <_>foo</_>
  </row>
</table>'];
        yield [[['foo' => 'bar'], ['baz', 'buz' => 'here']], '<?xml version="1.0" encoding="UTF-8"?>
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
</table>'];
    }

    public function testInvalidName()
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage("Invalid name '0'");
        $xmlRenderer = new XmlRenderer();
        $nullOutput = new NullOutput();
        $xmlRenderer->render($nullOutput, [['foo']]);
    }

    public function testInvalidEncoding()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Encoding error, only US-ASCII and UTF-8 supported, can not process '");
        $xmlRenderer = new XmlRenderer();
        $nullOutput = new NullOutput();
        $xmlRenderer->render($nullOutput, [["\xC1" => 'foo']]);
    }

    /**
     * @dataProvider provideTables
     */
    public function testTableRendering($rows, $expected)
    {
        $xmlRenderer = new XmlRenderer();
        $streamOutput = new StreamOutput(fopen('php://memory', 'wb', false));

        $xmlRenderer->render($streamOutput, $rows);

        $this->assertSame($expected . "\n", $this->getOutputBuffer($streamOutput));
    }
}
