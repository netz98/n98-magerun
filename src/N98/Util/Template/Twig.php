<?php

namespace N98\Util\Template;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class Twig
{
    /**
     * @var Environment
     */
    protected $twigEnv;

    /**
     * @param array $baseDirs
     */
    public function __construct(array $baseDirs)
    {
        $loader = new FilesystemLoader($baseDirs);
        $this->twigEnv = new Environment($loader, ['debug' => true]);
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
        $twig = new Environment(new ArrayLoader(['debug' => true]));
        $this->addExtensions($twig);
        $this->addFilters($twig);

        return $twig->render($string, $variables);
    }

    /**
     * @param Environment $twig
     */
    protected function addFilters(Environment $twig)
    {
        /**
         * cast_to_array
         */
        $twig->addFilter(
            new TwigFilter('cast_to_array', [$this, 'filterCastToArray'])
        );
    }

    /**
     * @param Environment $twig
     */
    protected function addExtensions(Environment $twig)
    {
        $twig->addExtension(new DebugExtension());
    }

    /**
     * @param \stdClass $stdClassObject
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
