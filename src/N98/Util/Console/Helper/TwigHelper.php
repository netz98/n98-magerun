<?php

declare(strict_types=1);

namespace N98\Util\Console\Helper;

use Exception;
use N98\Magento\Application\Config;
use N98\Util\Template\Twig;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;

/**
 * Helper to render twig templates
 *
 * @package N98\Util\Console\Helper
 */
class TwigHelper extends Helper
{
    protected Twig $twig;

    /**
     * @throws RuntimeException
     */
    public function __construct(Config $config)
    {
        $baseDirs = $this->getBaseDirsFromConfig($config);

        try {
            $this->twig = new Twig($baseDirs);
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Renders a twig template file
     */
    public function render(string $template, array $variables = []): string
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * Renders a twig string
     */
    public function renderString(string $string, array $variables = []): string
    {
        return $this->twig->renderString($string, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'twig';
    }

    private function getBaseDirsFromConfig(Config $config): array
    {
        $baseDir = __DIR__ . '/../../../../..'; # root of project source tree

        $baseDirs = [];

        $dirs = array_reverse($config->getConfig(['twig', 'baseDirs']));

        foreach ($dirs as $dir) {
            if (!is_string($dir)) {
                continue;
            }

            if (2 > strlen($dir)) {
                continue;
            }

            if ('./' === substr($dir, 0, 2)) {
                $dir = $baseDir . substr($dir, 1);
            }

            $baseDirs[] = $dir;
        }

        return $baseDirs;
    }
}
