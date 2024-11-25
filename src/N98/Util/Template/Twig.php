<?php

declare(strict_types=1);

namespace N98\Util\Template;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Class Twig
 *
 * @package N98\Util\Template
 */
class Twig
{
    /**
     * @var Environment
     */
    protected $twigEnv;

    public function __construct(array $baseDirs)
    {
        $filesystemLoader = new FilesystemLoader($baseDirs);
        $this->twigEnv = new Environment($filesystemLoader, ['debug' => true]);
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
        $twigEnvironment = new Environment(new ArrayLoader(['debug' => true]));
        $this->addExtensions($twigEnvironment);
        $this->addFilters($twigEnvironment);

        return $twigEnvironment->render($string, $variables);
    }

    protected function addFilters(Environment $twigEnvironment)
    {
        /**
         * cast_to_array
         */
        $twigEnvironment->addFilter(
            new TwigFilter('cast_to_array', [$this, 'filterCastToArray'])
        );
    }

    protected function addExtensions(Environment $twigEnvironment)
    {
        $twigEnvironment->addExtension(new DebugExtension());
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

        return array_map(__METHOD__, $stdClassObject);
    }
}
