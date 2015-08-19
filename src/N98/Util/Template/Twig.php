<?php

namespace N98\Util\Template;

use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_Loader_String;
use Twig_Extension_Debug;
use Twig_SimpleFilter;

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
        $loader = new Twig_Loader_Filesystem($baseDirs);
        $this->twigEnv = new Twig_Environment($loader, array('debug' => true));
        $this->addExtensions($this->twigEnv);
        $this->addFilters($this->twigEnv);
    }

    /**
     * @param string $filename
     * @param array $variables
     *
     * @return string
     */
    public function render($filename, $variables)
    {
        return $this->twigEnv->render($filename, $variables);
    }

    /**
     * @param string $string
     * @param array  $variables
     *
     * @return string
     */
    public function renderString($string, $variables)
    {
        $loader = new Twig_Loader_String();
        $twig = new Twig_Environment($loader, array('debug' => true));
        $this->addExtensions($twig);
        $this->addFilters($twig);

        return $twig->render($string, $variables);
    }

    /**
     * @param Twig_Environment $twig
     */
    protected function addFilters(Twig_Environment $twig)
    {
        /**
         * cast_to_array
         */
        $twig->addFilter(
            new Twig_SimpleFilter('cast_to_array', array($this, 'filterCastToArray'))
        );
    }

    /**
     * @param Twig_Environment $twig
     */
    protected function addExtensions(Twig_Environment $twig)
    {
        $twig->addExtension(new Twig_Extension_Debug());
    }

    /**
     * @param stdClass $stdClassObject
     *
     * @return array
     */
    public static function filterCastToArray($stdClassObject)
    {
        if (is_object($stdClassObject)) {
            $stdClassObject = get_object_vars($stdClassObject);
        }
        if (is_array($stdClassObject)) {
            return array_map(__METHOD__, $stdClassObject);
        } else {
            return $stdClassObject;
        }
    }
}
