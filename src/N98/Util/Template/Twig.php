<?php

namespace N98\Util\Template;

use \Twig_Environment;
use \Twig_Loader_Filesystem;
use \Twig_Loader_String;

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
     *
     * @return mixed
     */
    public function render($filename, $variables)
    {
        return $this->twigEnv->render($filename, $variables);
    }

    /**
     * @param string $string
     * @param array  $variables
     *
     * @return mixed
     */
    public function renderString($string, $variables)
    {
        $loader = new \Twig_Loader_String();
        $twig = new \Twig_Environment($loader);
        return $twig->render($string, $variables);
    }
}