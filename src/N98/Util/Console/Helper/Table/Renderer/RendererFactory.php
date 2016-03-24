<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

class RendererFactory
{
    protected static $formats = array(
        'csv'  => 'N98\Util\Console\Helper\Table\Renderer\CsvRenderer',
        'json' => 'N98\Util\Console\Helper\Table\Renderer\JsonRenderer',
        'text' => 'N98\Util\Console\Helper\Table\Renderer\TextRenderer',
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
     * @param string $format
     * @param OutputInterface $output
     * @param array $rows
     */
    public static function render($format, OutputInterface $output, array $rows)
    {
        $factory = new self;

        if (!$renderer = $factory->create($format)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown format %s, known formats are: %s',
                    var_export($format, true),
                    implode(',', self::getFormats())
                )
            );
        }

        $renderer->render($output, $rows);
    }

    /**
     * @return array
     */
    public static function getFormats()
    {
        return array_keys(self::$formats);
    }
}
