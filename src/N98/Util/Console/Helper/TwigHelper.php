<?php

namespace N98\Util\Console\Helper;

use Exception;
use N98\Magento\Application\Config;
use N98\Util\Template\Twig;
use RuntimeException;
use Symfony\Component\Console\Helper\Helper;

/**
 * Helper to render twig templates
 */
class TwigHelper extends Helper
{
    /**
     * @var \N98\Util\Template\Twig
     */
    protected $twig;

    /**
     * @param Config $config
     * @throws RuntimeException
     */
    public function __construct(Config $config)
    {
        $baseDirs = $this->getBaseDirsFromConfig($config);

        try {
            $this->twig = new Twig($baseDirs);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Renders a twig template file
     *
     * @param string $template
     * @param array $variables
     * @return mixed
     */
    public function render($template, $variables = array())
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * Renders a twig string
     *
     * @param       $string
     * @param array $variables
     *
     * @return string
     */
    public function renderString($string, $variables = array())
    {
        return $this->twig->renderString($string, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'twig';
    }

    /**
     * @param Config $config
     * @return array
     */
    private function getBaseDirsFromConfig(Config $config)
    {
        $baseDir = __DIR__ . '/../../../../..'; # root of project source tree

        $baseDirs = array();

        $dirs = array_reverse($config->getConfig(array('twig', 'baseDirs')));

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
