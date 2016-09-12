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
    private $className;

    /**
     * @var bool
     */
    private $exists;

    /**
     * @param string $className
     *
     * @return ClassUtil
     */
    public static function create($className)
    {
        return new self($className);
    }

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function exists()
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
    public function isA(ClassUtil $class)
    {
        return is_a($this->className, $class->className, true);
    }
}
