<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Developer\Module\Rewrite;

final class ClassUtil
{
    /**
     * @var string
     */
    private string $className;

    /**
     * @var bool|null
     */
    private ?bool $exists;

    /**
     * @param string $className
     *
     * @return ClassUtil
     */
    public static function create(string $className): ClassUtil
    {
        return new self($className);
    }

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        if (null === $this->exists) {
            $this->exists = ClassExistsChecker::create($this->className)->existsExtendsSafe();
        }

        return $this->exists;
    }

    /**
     * This class is a $class (is or inherits from it)
     *
     * @param ClassUtil $class
     * @return bool
     */
    public function isA(ClassUtil $class): bool
    {
        return is_a($this->className, $class->className, true);
    }
}
