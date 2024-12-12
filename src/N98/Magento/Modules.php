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
 *
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class Modules implements IteratorAggregate, Countable
{
    private ?array $list;

    public function __construct(?array $list = null)
    {
        if (null === $list) {
            $list = [];
        }

        $this->list = $list;
    }

    public function findInstalledModules(): Modules
    {
        $list = [];

        $modulesNode = Mage::app()->getConfig()->getNode('modules');
        if ($modulesNode) {
            $modules = $modulesNode->asArray();
            foreach ($modules as $moduleName => $moduleInfo) {
                $codePool   = $moduleInfo['codePool'] ?? '';
                $version    = $moduleInfo['version'] ?? '';
                $active     = $moduleInfo['active'] ?? '';

                $list[] = [
                    'codePool' => trim($codePool),
                    'Name'     => trim($moduleName),
                    'Version'  => trim($version),
                    'Status'   => StringTyped::formatActive($active),
                ];
            }
        }

        return new Modules($list);
    }

    /**
     * Filter modules by codepool, status and vendor if such options were inputted by user
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
            $filtered = ArrayFunctions::matrixFilterStartsWith($filtered, 'Name', $input->getOption('vendor'));
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
