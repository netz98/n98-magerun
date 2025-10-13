<?php

declare(strict_types=1);

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
    /**
     * @var array<string, class-string>
     */
    protected static array $formats = [
        'csv'  => CsvRenderer::class,
        'json' => JsonRenderer::class,
        'text' => TextRenderer::class,
        'xml'  => XmlRenderer::class,
    ];

    public function create(?string $format): ?RendererInterface
    {
        $format = is_null($format) ? $format : strtolower($format);
        if (isset(self::$formats[$format])) {
            $rendererClass = self::$formats[$format];
            /** @var RendererInterface $renderer */
            $renderer = new $rendererClass();
            return $renderer;
        }

        return null;
    }

    public static function render(string $format, OutputInterface $output, array $rows): void
    {
        $factory = new self();

        if (!($renderer = $factory->create($format)) instanceof \N98\Util\Console\Helper\Table\Renderer\RendererInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown format %s, known formats are: %s',
                    var_export($format, true),
                    implode(',', self::getFormats()),
                ),
            );
        }

        $renderer->render($output, $rows);
    }

    public static function getFormats(): array
    {
        return array_keys(self::$formats);
    }
}
