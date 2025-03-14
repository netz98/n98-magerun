<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper\Table\Renderer;

use PHPUnit\Framework\TestCase;

final class RenderFactoryTest extends TestCase
{
    /**
     * @covers \N98\Util\Console\Helper\Table\Renderer\RendererFactory::getFormats
     */
    public function testCreate()
    {
        $rendererFactory = new RendererFactory();

        $csv = $rendererFactory->create('csv');
        $this->assertInstanceOf(CsvRenderer::class, $csv);

        $json = $rendererFactory->create('json');
        $this->assertInstanceOf(JsonRenderer::class, $json);

        $xml = $rendererFactory->create('xml');
        $this->assertInstanceOf(XmlRenderer::class, $xml);

        $invalidFormat = $rendererFactory->create('invalid_format');
        $this->assertNotInstanceOf(\N98\Util\Console\Helper\Table\Renderer\RendererInterface::class, $invalidFormat);
    }
}
