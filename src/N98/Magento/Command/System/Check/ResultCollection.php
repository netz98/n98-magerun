<?php

namespace N98\Magento\Command\System\Check;

use Traversable;

/**
 * Class ResultCollection
 *
 * @package N98\Magento\Command\System\Check
 */
class ResultCollection implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected $_results;

    /**
     * @var string
     */
    protected $_resultGroup;

    /**
     * @param Result $result
     * @return $this
     */
    public function addResult(Result $result)
    {
        $this->_results[] = $result;

        return $this;
    }

    /**
     * @param string $status
     * @param string $message
     * @return Result
     */
    public function createResult($status = Result::STATUS_OK, $message = '')
    {
        $result = new Result($status, $message);
        $result->setResultGroup($this->_resultGroup);
        $this->addResult($result);

        return $result;
    }

    /**
     * @param string $resultGroup
     */
    public function setResultGroup($resultGroup)
    {
        $this->_resultGroup = $resultGroup;
    }

    /**
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     */
    public function getIterator()
    {
        return new \ArrayObject($this->_results);
    }
}
