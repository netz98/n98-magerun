<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Module\Rewrite;

use BadMethodCallException;
use Exception;
use N98\Util\AutoloadHandler;
use stdClass;

/**
 * More robust (against fatal errors in the inheritance chain) class_exists checker
 *
 * @package N98\Magento\Command\Developer\Module\Rewrite
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
final class ClassExistsChecker
{
    private string $className;

    private ?stdClass $context;

    public static function create(string $className): ClassExistsChecker
    {
        return new self($className);
    }

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Check for class-existence while handling conditional definition of classes that extend from non-existent classes
     * as it can happen with Magento Varien_Autoload that is using include to execute files for class definitions.
     */
    public function existsExtendsSafe(): bool
    {
        $context = $this->startContext();
        try {
            $exists = class_exists($this->className);
        } catch (Exception $exception) {
            return $this->exceptionContext($context, $exception);
        }

        $this->endContext($context);

        return $exists;
    }

    private function startContext(): stdClass
    {
        $context = new stdClass();
        $context->lastException = null;
        $context->stack = [];
        $context->terminator = AutoloadHandler::create([$this, 'autoloadTerminator']);
        $context->className = $this->className;

        return $this->context = $context;
    }

    private function exceptionContext(stdClass $context, Exception $exception): bool
    {
        /** @var AutoloadHandler $terminator */
        $terminator = $context->terminator;
        $terminator->reset();

        if ($exception !== $context->lastException) {
            $message = sprintf('Exception when checking for class %s existence', $context->className);
            throw new ClassExistsThrownException($message, 0, $exception);
        }

        return false;
    }

    private function endContext(stdClass $context): void
    {
        if (isset($context->terminator)) {
            /** @var AutoloadHandler $terminator */
            $terminator = $context->terminator;
            $terminator->reset();
        }

        $this->context = null;
    }

    /**
     * Method is called as last autoloader (if all others have failed), so the class does not exist (is not
     * resolve-able)
     *
     * @throws CanNotAutoloadCollaboratorClassException
     */
    public function autoloadTerminator(string $notFoundClass): void
    {
        $className = $this->className;
        if (is_null($context = $this->context)) {
            // @codeCoverageIgnoreStart
            // sanity check, should never come here
            throw new BadMethodCallException('No autoload in place');
            // @codeCoverageIgnoreStop
        }

        if ($notFoundClass === $className) {
            return;
        }

        $context->stack[] = [$notFoundClass, $className];

        $context->lastException = new CanNotAutoloadCollaboratorClassException(
            sprintf('%s for %s', $notFoundClass, $className),
        );
        throw $context->lastException;
    }
}
