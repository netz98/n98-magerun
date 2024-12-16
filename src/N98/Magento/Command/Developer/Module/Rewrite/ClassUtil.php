<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

/**
 * Class ClassUtil
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
final class ClassUtil
{
    private string $className;

    private ?bool $exists = null;

    public static function create(string $className): ClassUtil
    {
        return new self($className);
    }

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function exists(): ?bool
    {
        if (is_null($this->exists)) {
            $this->exists = ClassExistsChecker::create($this->className)->existsExtendsSafe();
        }

        return $this->exists;
    }

    /**
     * This class is a $class (is or inherits from it)
     */
    public function isA(ClassUtil $classUtil): bool
    {
        return is_a($this->className, $classUtil->className, true);
    }
}
