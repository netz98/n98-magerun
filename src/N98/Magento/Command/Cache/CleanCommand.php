<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Exception;
use N98\Magento\MageMethods as Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean cache command
 *
 * @package N98\Magento\Command\Cache
 */
class CleanCommand extends AbstractCacheCommandReinit
{
    public const COMMAND_ARGUMENT_TYPE = 'type';

    /**
     * @var string
     */
    protected static $defaultName = 'cache:clean';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Clean cache.';

    protected static bool $initMagentoSoftFlag = true;

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_TYPE,
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Cache type code like "config"'
            )
        ;

        parent::configure();
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Cleans expired cache entries.

If you would like to clean only one cache type use like:

   <info>$ n98-magerun.phar cache:clean block_html</info>

If you would like to clean multiple cache types at once use like:

   <info>$ n98-magerun.phar cache:clean block_html eav</info>

If you would like to remove all cache entries use <info>cache:flush</info>

<comment>Options:</comment>
    <info>--reinit</info> Reinitialise the config cache after cleaning (Default)
    <info>--no-reinit</info> Don't reinitialise the config cache after cleaning

HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @uses Mage::app()
     * @uses Mage::dispatchEvent()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $noReinitOption = $input->getOption(self::COMMAND_OPTION_NO_REINIT);
        if (!$noReinitOption) {
            $this->banUseCache();
        }

        try {
            Mage::app()->loadAreaPart('adminhtml', 'events');
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $allTypes = Mage::app()->getCacheInstance()->getTypes();
        /** @var array<int, string> $typesToClean */
        $typesToClean = $input->getArgument(self::COMMAND_ARGUMENT_TYPE);
        $this->validateCacheCodes($typesToClean);
        $typeKeys = array_keys($allTypes);

        foreach ($typeKeys as $type) {
            if ((is_countable($typesToClean) ? count($typesToClean) : 0) == 0 || in_array($type, $typesToClean)) {
                Mage::app()->getCacheInstance()->cleanType($type);
                Mage::dispatchEvent('adminhtml_cache_refresh_type', ['type' => $type]);
                $output->writeln('<info>Cache <comment>' . $type . '</comment> cleaned</info>');
            }
        }

        if (!$noReinitOption) {
            $this->reinitCache();
        }

        return Command::SUCCESS;
    }
}
