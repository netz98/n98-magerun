<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check;

use LogicException;

/**
 * Class Result
 *
 * @package N98\Magento\Command\System\Check
 */
class Result
{
    /**
     * @var string
     */
    public const STATUS_OK = 'ok';

    /**
     * @var string
     */
    public const STATUS_ERROR = 'error';

    /**
     * @var string
     */
    public const STATUS_WARNING = 'warning';

    /**
     * @var string
     */
    protected $_status;

    /**
     * @var string|string[]
     */
    protected $_message;

    protected string $_resultGroup;

    /**
     * @param bool|string $status
     * @param string|string[] $message
     */
    public function __construct($status = self::STATUS_OK, $message = '', string $resultGroup = '')
    {
        $this->_status = $status;
        $this->_message = $message;
        $this->_resultGroup = $resultGroup;
    }

    public function isValid(): bool
    {
        return $this->_status === self::STATUS_OK;
    }

    /**
     * @param bool|string $status
     * @return $this
     */
    public function setStatus($status)
    {
        if (is_bool($status)) {
            $status = $status ? self::STATUS_OK : self::STATUS_ERROR;
        }

        if (!in_array($status, [self::STATUS_OK, self::STATUS_ERROR, self::STATUS_WARNING])) {
            throw new LogicException(
                'Wrong status was given. Use constants: Result::OK, Result::ERROR, Result::WARNING'
            );
        }

        $this->_status = $status;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->_status;
    }

    /**
     * @return string|string[]
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message)
    {
        $this->_message = $message;
        return $this;
    }

    public function getResultGroup(): string
    {
        return $this->_resultGroup;
    }

    public function setResultGroup(string $resultGroup): void
    {
        $this->_resultGroup = $resultGroup;
    }
}
