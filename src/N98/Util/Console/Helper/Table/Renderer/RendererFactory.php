<?php

namespace N98\Util\Console\Helper\Table\Renderer;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RendererFactory
 *
 * @package N98\Util\Console\Helper\Table\Renderer
 */
class RendererFactory
{
    protected static $formats = ['csv'  => CsvRenderer::class, 'json' => JsonRenderer::class, 'text' => TextRenderer::class, 'xml'  => XmlRenderer::class];

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

            return new $rendererClass();
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
        $factory = new self();

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
