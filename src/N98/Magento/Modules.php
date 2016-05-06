<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

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
            $list = array();
        }

        $this->list = $list;
    }

    /**
     * @return Modules
     */
    public function findInstalledModules()
    {
        $list = array();

        $modules = Mage::app()->getConfig()->getNode('modules')->asArray();
        foreach ($modules as $moduleName => $moduleInfo) {
            $codePool = isset($moduleInfo['codePool']) ? $moduleInfo['codePool'] : '';
            $version = isset($moduleInfo['version']) ? $moduleInfo['version'] : '';
            $active = isset($moduleInfo['active']) ? $moduleInfo['active'] : '';

            $list[] = array(
                'Code pool' => trim($codePool),
                'Name'      => trim($moduleName),
                'Version'   => trim($version),
                'Status'    => StringTyped::formatActive($active),
            );
        }

        $installed = new Modules();
        $installed->list = $list;

        return $installed;
    }

    /**
     * Filter modules by codepool, status and vendor if such options were inputted by user
     *
     * @param InputInterface $input
     * @return Modules
     */
    public function filterModules(InputInterface $input)
    {
        $list = $this->list;

        if ($input->getOption('codepool')) {
            $list = ArrayFunctions::matrixFilterByValue($list, "Code pool", $input->getOption('codepool'));
        }

        if ($input->getOption('status')) {
            $list = ArrayFunctions::matrixFilterByValue($list, 'Status', $input->getOption('status'));
        }

        if ($input->getOption('vendor')) {
            $list = ArrayFunctions::matrixFilterStartswith($list, 'Name', $input->getOption('vendor'));
        }

        $filtered = new self();
        $filtered->list = $list;

        return $filtered;
    }

    ### Traversable Interface ###

    /**
     * Retrieve an external iterator
     *
     * @return Traversable|array[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->list);
    }

    ### Countable Interface ###

    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->list);
    }
}
