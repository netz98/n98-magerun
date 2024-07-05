<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use Exception;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flush cache command
 *
 * @package N98\Magento\Command\Cache
 */
class FlushCommand extends AbstractCacheCommandReinit
{
    /**
     * @var string
     */
    protected static $defaultName = 'cache:flush';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Flush cache storage.';

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Flush the entire cache.

   $ n98-magerun.phar cache:flush [--reinit --no-reinit]

Options:
    --reinit Reinitialise the config cache after flushing (Default)
    --no-reinit Don't reinitialise the config cache after flushing
HELP;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->detectMagento($output);

        if (!$input->getOption(self::COMMAND_OPTION_NO_REINIT)) {
            $this->banUseCache();
        }

        $this->initMagento();
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
        try {
            Mage::app()->loadAreaPart('adminhtml', 'events');
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        Mage::dispatchEvent('adminhtml_cache_flush_all', ['output' => $output]);
        $result = Mage::app()->getCacheInstance()->flush();
        if ($result) {
            $output->writeln('<info>Cache cleared</info>');
        } else {
            $output->writeln('<error>Failed to clear Cache</error>');
        }

        if (!$input->getOption(self::COMMAND_OPTION_NO_REINIT)) {
            $this->reinitCache();
        }

        return Command::SUCCESS;
    }
}
