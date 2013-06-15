<?php

namespace N98\Util\Console\Helper;

use N98\Util\Template\Twig;
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
     * @param array $baseDirs
     * @throws \RuntimeException
     */
    public function __construct(array $baseDirs)
    {
        try {
            $this->twig = new Twig($baseDirs);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * @param string $template
     * @param array  $variables
     */
    public function render($template, $variables = array())
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'twig';
    }
}