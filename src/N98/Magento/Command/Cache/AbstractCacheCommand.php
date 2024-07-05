<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use InvalidArgumentException;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Varien_Object;

use function array_key_exists;
use function is_null;

/**
 * Abstract cache command
 *
 * @package N98\Magento\Command\Cache
 */
abstract class AbstractCacheCommand extends AbstractCommand
{
    /**
     * @var array<string, Varien_Object>|null
     */
    protected ?array $cacheTypes = null;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->detectMagento($output);
        $this->initMagento();
    }

    /**
     * @param string[] $codes
     * @throws InvalidArgumentException
     */
    protected function validateCacheCodes(array $codes): void
    {
        $cacheTypes = $this->getAllCacheTypes();
        foreach ($codes as $cacheCode) {
            if (!array_key_exists($cacheCode, $cacheTypes)) {
                throw new InvalidArgumentException('Invalid cache type: ' . $cacheCode);
            }
        }
    }

    /**
     * @return array<string, Varien_Object>
     *
     * @uses Mage::app()
     */
    protected function getAllCacheTypes(): array
    {
        if (is_null($this->cacheTypes)) {
            $this->cacheTypes = Mage::app()->getCacheInstance()->getTypes();
        }
        return $this->cacheTypes;
    }
}
