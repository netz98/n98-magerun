<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache\Toggle;

use N98\Magento\Command\Cache\AbstractCacheCommand;
use N98\Magento\Methods\MageBase as Mage;
use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function count;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Abstract cache toggle class
 *
 * @package N98\Magento\Command\Cache\Toggle
 */
abstract class AbstractCacheCommandToggle extends AbstractCacheCommand
{
    public const COMMAND_ARGUMENT_CODE = 'code';

    /**
     * @var bool
     */
    protected static bool $cacheStatus;

    /**
     * @var string
     */
    protected static string $toggleName;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                static::COMMAND_ARGUMENT_CODE,
                InputArgument::OPTIONAL,
                'Code of cache (Multiple codes operated by comma)'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @uses Mage::app()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $codeArgument = BinaryString::trimExplodeEmpty(',', $input->getArgument(self::COMMAND_ARGUMENT_CODE));
        $this->saveCacheStatus($codeArgument, static::$cacheStatus);

        if (static::$cacheStatus === false) {
            if (empty($codeArgument)) {
                Mage::app()->getCacheInstance()->flush();
            } else {
                foreach ($codeArgument as $type) {
                    Mage::app()->getCacheInstance()->cleanType($type);
                }
            }
        }

        if (count($codeArgument) > 0) {
            foreach ($codeArgument as $code) {
                $output->writeln(sprintf('<info>Cache <comment>%s</comment> %s</info>', $code, static::$toggleName));
            }
        } else {
            $output->writeln(sprintf('<info>Caches %s</info>', static::$toggleName));
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $codeArgument
     * @param bool $status
     *
     * @return void
     *
     * @uses Mage::app()
     */
    private function saveCacheStatus(array $codeArgument, bool $status): void
    {
        $this->validateCacheCodes($codeArgument);

        $cacheTypes = $this->getAllCacheTypes();
        $enable = Mage::app()->useCache();

        if (is_array($enable)) {
            foreach (array_keys($cacheTypes) as $cacheCode) {
                if (empty($codeArgument) || in_array($cacheCode, $codeArgument)) {
                    $enable[$cacheCode] = $status ? 1 : 0;
                }
            }

            Mage::app()->saveUseCache($enable);
        }
    }
}
