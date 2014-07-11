<?php

namespace N98\Magento\Command\System\Check;

class Result
{
    /**
     * @type string
     */
    const STATUS_OK = 'ok';

    /**
     * @type string
     */
    const STATUS_ERROR = 'error';

    /**
     * @type string
     */
    const STATUS_WARNING = 'warning';

    /**
     * @var bool
     */
    protected $_status;

    /**
     * @var array[string]
     */
    protected $_messages;

    /**
     * @var string
     */
    protected $_resultGroup;

    public function __construct($status = self::STATUS_OK, $message = '', $resultGroup = '')
    {
        $this->_status = $status;
        $this->_message = $message;
        $this->_resultGroup = $resultGroup;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->_status === self::STATUS_OK;
    }

    /**
     * @param boolean $status
     * @return $this
     */
    public function setStatus($status)
    {
        if (!in_array($status, array(self::STATUS_OK, self::STATUS_ERROR, self::STATUS_WARNING))) {
            throw new \LogicException('Wrong status was given. Use constants: Result::OK, Result::ERROR, Result::WARNING');
        }

        $this->_status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
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