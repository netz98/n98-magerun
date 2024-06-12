<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract cache toggle class
 *
 * @package N98\Magento\Command\Cache
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

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_CODE,
                InputArgument::OPTIONAL,
                'Code of cache (Multiple codes operated by comma)'
            );

        parent::configure();
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $codeArgument = BinaryString::trimExplodeEmpty(',', $input->getArgument(self::COMMAND_ARGUMENT_CODE));
        $this->saveCacheStatus($codeArgument, static::$cacheStatus);

        if (static::$cacheStatus === false) {
            if (empty($codeArgument)) {
                $this->_getCacheModel()->flush();
            } else {
                foreach ($codeArgument as $type) {
                    $this->_getCacheModel()->cleanType($type);
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
     * @return void
     */
    protected function saveCacheStatus(array $codeArgument, bool $status): void
    {
        $this->validateCacheCodes($codeArgument);

        $cacheTypes = $this->_getCacheModel()->getTypes();
        $enable = $this->_getMage()->useCache();

        if (is_array($enable)) {
            foreach ($cacheTypes as $cacheCode => $cacheModel) {
                if (empty($codeArgument) || in_array($cacheCode, $codeArgument)) {
                    $enable[$cacheCode] = $status ? 1 : 0;
                }
            }

            $this->_getMage()->saveUseCache($enable);
        }
    }
}
