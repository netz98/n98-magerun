<?php

namespace N98\Util\Template;

use \Twig_Environment;
use \Twig_Loader_Filesystem;

class Twig
{
    /**
     * @var \Twig_Environment
     */
    protected $twigEnv;

    /**
     * @param array $baseDirs
     */
    public function __construct(array $baseDirs)
    {
        $loader = new \Twig_Loader_Filesystem($baseDirs);
        $this->twigEnv = new \Twig_Environment($loader);
    }

    /**
     * @param string $filename
     * @param array $variables
     */
    public function render($filename, $variables)
    {
        return $this->twigEnv->render($filename, $variables);
    }
}