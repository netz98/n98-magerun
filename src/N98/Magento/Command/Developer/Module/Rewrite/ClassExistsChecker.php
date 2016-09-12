<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento\Command\Developer\Module\Rewrite;

use BadMethodCallException;
use Exception;
use N98\Util\AutoloadHandler;
use stdClass;

/**
 * More robust (against fatal errors in the inheritance chain) class_exists checker
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 */
final class ClassExistsChecker
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var stdClass
     */
    private $context;

    /**
     * @param string $className
     *
     * @return ClassExistsChecker
     */
    public static function create($className)
    {
        return new self($className);
    }

    /**
     * ClassExistsChecker constructor.
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Check for class-existence while handling conditional definition of classes that extend from non-existent classes
     * as it can happen with Magento Varien_Autoload that is using include to execute files for class definitions.
     *
     * @return bool
     */
    public function existsExtendsSafe()
    {
        $context = $this->startContext();
        try {
            $exists = class_exists($this->className);
        } catch (Exception $ex) {
            return $this->exceptionContext($context, $ex);
        }
        $this->endContext($context);

        return $exists;
    }

    /**
     * @return stdClass
     */
    private function startContext()
    {
        $context = new stdClass();
        $context->lastException = null;
        $context->stack = array();
        $context->terminator = AutoloadHandler::create(array($this, 'autoloadTerminator'));
        $context->className = $this->className;

        return $this->context = $context;
    }

    /**
     * @param $context
     * @param Exception $ex
     * @return bool
     */
    private function exceptionContext($context, Exception $ex)
    {
        /** @var $terminator AutoloadHandler */
        $terminator = $context->terminator;
        $terminator->reset();

        if ($ex !== $context->lastException) {
            $message = sprintf('Exception when checking for class %s existence', $context->className);
            throw new ClassExistsThrownException($message, 0, $ex);
        }

        return false;
    }

    /**
     * @param $context
     */
    private function endContext($context)
    {
        if (isset($context->terminator)) {
            /** @var $terminator AutoloadHandler */
            $terminator = $context->terminator;
            $terminator->reset();
        }
        $this->context = null;
    }

    /**
     * Method is called as last auto-loader (if all others have failed), so the class does not exists (is not
     * resolve-able)
     *
     * @param $notFoundClass
     * @throws CanNotAutoloadCollaboratorClassException
     */
    public function autoloadTerminator($notFoundClass)
    {
        $className = $this->className;
        if (null === $context = $this->context) {
            //@codeCoverageIgnoreStart
            // sanity check, should never come here
            throw new BadMethodCallException('No autoloading in place');
            // @codeCoverageIgnoreStop
        }

        if ($notFoundClass === $className) {
            return;
        }

        $context->stack[] = array($notFoundClass, $className);

        $context->lastException = new CanNotAutoloadCollaboratorClassException(
            sprintf('%s for %s', $notFoundClass, $className)
        );
        throw $context->lastException;
    }
}
