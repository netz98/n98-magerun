<?php

namespace N98\Magento\Command\System\Check;

class Result
{
    /**
     * @var bool
     */
    protected $_isValid;

    /**
     * @var array[string]
     */
    protected $_messages;

    /**
     * @var string
     */
    protected $_resultGroup;

    public function __construct($isValid = true, $message = '', $resultGroup = '')
    {
        $this->_isValid = $isValid;
        $this->_message = $message;
        $this->_resultGroup = $resultGroup;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->_isValid;
    }

    /**
     * @param boolean $isValid
     * @return $this
     */
    public function setIsValid($isValid)
    {
        $this->_isValid = $isValid;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->_message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getResultGroup()
    {
        return $this->_resultGroup;
    }

    /**
     * @param string $resultGroup
     */
    public function setResultGroup($resultGroup)
    {
        $this->_resultGroup = $resultGroup;
    }
}