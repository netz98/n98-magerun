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
    protected array $_results;

    protected string $_resultGroup;

    /**
     * @return $this
     */
    public function addResult(Result $result)
    {
        $this->_results[] = $result;
        return $this;
    }

    public function createResult(string $status = Result::STATUS_OK, string $message = ''): Result
    {
        $result = new Result($status, $message);
        $result->setResultGroup($this->_resultGroup);
        $this->addResult($result);

        return $result;
    }

    public function setResultGroup(string $resultGroup): void
    {
        $this->_resultGroup = $resultGroup;
    }

    public function getIterator(): Traversable
    {
        return new ArrayObject($this->_results);
    }
}
