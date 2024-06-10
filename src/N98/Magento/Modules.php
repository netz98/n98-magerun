<?php

declare(strict_types=1);

namespace N98\Magento;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Mage;
use N98\Util\ArrayFunctions;
use N98\Util\StringTyped;
use Symfony\Component\Console\Input\InputInterface;
use Traversable;

/**
 * Magento Modules
 *
 * @package N98\Magento
 */
class Modules implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    private $list;

    public function __construct(array $list = null)
    {
        if (null === $list) {
            $list = [];
        }

        $this->list = $list;
    }

    /**
     * @return Modules
     */
    public function findInstalledModules(): Modules
    {
        $list = [];

        $modules = Mage::app()->getConfig()->getNode('modules')->asArray();
        foreach ($modules as $moduleName => $moduleInfo) {
            $codePool = $moduleInfo['codePool'] ?? '';
            $version = $moduleInfo['version'] ?? '';
            $active = $moduleInfo['active'] ?? '';

            $list[] = [
                'codePool' => trim($codePool),
                'Name'     => trim($moduleName),
                'Version'  => trim($version),
                'Status'   => StringTyped::formatActive($active)
            ];
        }

        return new Modules($list);
    }

    /**
     * Filter modules by codepool, status and vendor if such options were inputted by user
     *
     * @param InputInterface $input
     * @return Modules
     */
    public function filterModules(InputInterface $input): Modules
    {
        $filtered = $this->list;

        if ($input->getOption('codepool')) {
            $filtered = ArrayFunctions::matrixFilterByValue($filtered, 'codePool', $input->getOption('codepool'));
        }

        if ($input->getOption('status')) {
            $filtered = ArrayFunctions::matrixFilterByValue($filtered, 'Status', $input->getOption('status'));
        }

        if ($input->getOption('vendor')) {
            $filtered = ArrayFunctions::matrixFilterStartswith($filtered, 'Name', $input->getOption('vendor'));
        }

        return new self($filtered);
    }

    ### Traversable Interface ###

    /**
     * Retrieve an external iterator
     *
     * @return ArrayIterator|array[]
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->list);
    }

    ### Countable Interface ###

    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->list);
    }
}
