<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check;

use ArrayObject;
use IteratorAggregate;
use Traversable;

/**
 * Class ResultCollection
 *
 * @package N98\Magento\Command\System\Check
 */
class ResultCollection implements IteratorAggregate
{
    /**
     * @var array
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    protected array $_results;

    /**
     * @var string
     */
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    protected string $_resultGroup;

    /**
     * @param Result $result
     * @return $this
     */
    public function addResult(Result $result): ResultCollection
    {
        $this->_results[] = $result;

        return $this;
    }

    /**
     * @param string $status
     * @param string $message
     * @return Result
     */
    public function createResult(string $status = Result::STATUS_OK, string $message = ''): Result
    {
        $result = new Result($status, $message);
        $result->setResultGroup($this->_resultGroup);
        $this->addResult($result);

        return $result;
    }

    /**
     * @param string $resultGroup
     */
    public function setResultGroup(string $resultGroup): void
    {
        $this->_resultGroup = $resultGroup;
    }

    /**
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     */
    public function getIterator(): Traversable
    {
        return new ArrayObject($this->_results);
    }
}
