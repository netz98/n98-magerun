<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use PHPUnit\Framework\TestCase;
class RenderFactoryTest extends TestCase
{
    /**
     * @covers \N98\Util\Console\Helper\Table\Renderer\RendererFactory::getFormats
     */
    public function testCreate()
    {
        $rendererFactory = new RendererFactory();

        $csv = $rendererFactory->create('csv');
        self::assertInstanceOf(CsvRenderer::class, $csv);

        $json = $rendererFactory->create('json');
        self::assertInstanceOf(JsonRenderer::class, $json);

        $xml = $rendererFactory->create('xml');
        self::assertInstanceOf(XmlRenderer::class, $xml);

        $invalidFormat = $rendererFactory->create('invalid_format');
        self::assertFalse($invalidFormat);
    }
}
