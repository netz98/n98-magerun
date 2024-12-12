<?php

declare(strict_types=1);

namespace N98\Util\Template;

use stdClass;
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
    protected Environment $twigEnv;

    public function __construct(array $baseDirs)
    {
        $filesystemLoader = new FilesystemLoader($baseDirs);
        $this->twigEnv = new Environment($filesystemLoader, ['debug' => true]);
        $this->addExtensions($this->twigEnv);
        $this->addFilters($this->twigEnv);
    }

    public function render(string $filename, array $variables): string
    {
        return $this->twigEnv->render($filename, $variables);
    }

    public function renderString(string $string, array $variables): string
    {
        $twigEnvironment = new Environment(new ArrayLoader(['debug' => true]));
        $this->addExtensions($twigEnvironment);
        $this->addFilters($twigEnvironment);

        return $twigEnvironment->render($string, $variables);
    }

    protected function addFilters(Environment $twigEnvironment): void
    {
        // cast_to_array
        $twigEnvironment->addFilter(
            new TwigFilter('cast_to_array', [$this, 'filterCastToArray']),
        );
    }

    protected function addExtensions(Environment $twigEnvironment): void
    {
        $twigEnvironment->addExtension(new DebugExtension());
    }

    /**
     * @param stdClass|mixed $stdClassObject
     */
    public static function filterCastToArray($stdClassObject): array
    {
        if (is_object($stdClassObject)) {
            $stdClassObject = get_object_vars($stdClassObject);
        }

        return array_map(__METHOD__, $stdClassObject);
    }
}
