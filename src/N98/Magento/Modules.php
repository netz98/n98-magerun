<?php

declare(strict_types=1);

namespace N98\Magento;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use N98\Magento\Command\Developer\Module\ListCommand;
use N98\Magento\Methods\MageBase as Mage;
use N98\Util\ArrayFunctions;
use N98\Util\StringTyped;
use Symfony\Component\Console\Input\InputInterface;
use Traversable;

use function count;
use function is_null;
use function trim;

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
    private array $list;

    public function __construct(array $list = null)
    {
        if (is_null($list)) {
            $list = [];
        }

        $this->list = $list;
    }

    /**
     * @return Modules
     *
     * @uses Mage::app()
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

        $codepool = $input->getOption(ListCommand::COMMAND_OPTION_COODPOOL);
        if ($codepool) {
            $filtered = ArrayFunctions::matrixFilterByValue($filtered, 'codePool', $codepool);
        }

        $status = $input->getOption(ListCommand::COMMAND_OPTION_STATUS);
        if ($status) {
            $filtered = ArrayFunctions::matrixFilterByValue($filtered, 'Status', $status);
        }

        /** @var string $vendor */
        $vendor = $input->getOption(ListCommand::COMMAND_OPTION_VENDOR);
        if ($vendor) {
            $filtered = ArrayFunctions::matrixFilterStartswith($filtered, 'Name', $vendor);
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
