<?php

namespace N98\Util\Console\Helper\Table\Renderer;

class RenderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \N98\Util\Console\Helper\Table\Renderer\RendererFactory::getFormats
     */
    public function testCreate()
    {
        $renderFactory = new RendererFactory();

        $csv = $renderFactory->create('csv');
        self::assertInstanceOf('N98\Util\Console\Helper\Table\Renderer\CsvRenderer', $csv);

        $json = $renderFactory->create('json');
        self::assertInstanceOf('N98\Util\Console\Helper\Table\Renderer\JsonRenderer', $json);

        $xml = $renderFactory->create('xml');
        self::assertInstanceOf('N98\Util\Console\Helper\Table\Renderer\XmlRenderer', $xml);

        $invalidFormat = $renderFactory->create('invalid_format');
        self::assertFalse($invalidFormat);
    }
}
