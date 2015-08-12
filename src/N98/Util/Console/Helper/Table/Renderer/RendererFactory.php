<?php

namespace N98\Util\Console\Helper\Table\Renderer;

class RendererFactory
{
    protected static $formats = array(
        'csv'  => 'N98\Util\Console\Helper\Table\Renderer\CsvRenderer',
        'json' => 'N98\Util\Console\Helper\Table\Renderer\JsonRenderer',
        'xml'  => 'N98\Util\Console\Helper\Table\Renderer\XmlRenderer',
    );

    /**
     * @param string $format
     *
     * @return bool|RendererInterface
     */
    public function create($format)
    {
        $format = strtolower($format);
        if (isset(self::$formats[$format])) {
            $rendererClass = self::$formats[$format];
            return new $rendererClass;
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getFormats()
    {
        return array_keys(self::$formats);
    }
}
