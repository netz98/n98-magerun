<?php

namespace N98\Magento\Command\Cache;

use Enterprise_PageCache_Model_Cache;
use Exception;
use Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flush cache command
 *
 * @package N98\Magento\Command\Cache
 */
class FlushCommand extends AbstractCacheCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:flush')
            ->addOption(
                'reinit',
                null,
                InputOption::VALUE_NONE,
                'Reinitialise the config cache after flushing'
            )
            ->addOption(
                'no-reinit',
                null,
                InputOption::VALUE_NONE,
                "Don't reinitialise the config cache after flushing"
            )
            ->setDescription('Flush magento cache storage')
        ;
    }

    /**
     * {@inheritdoc}
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        $noReinitOption = $input->getOption('no-reinit');
        if (!$noReinitOption) {
            $this->banUseCache();
        }

        if (!$this->initMagento()) {
            return 0;
        }

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

        if (!$noReinitOption) {
            $this->reinitCache();
        }

        /* Since Magento 1.10 we have an own cache handler for FPC */
        if ($this->isEnterpriseFullPageCachePresent() && class_exists('Enterprise_PageCache_Model_Cache')) {
            $result = Enterprise_PageCache_Model_Cache::getCacheInstance()->flush();
            if ($result) {
                $output->writeln('<info>FPC cleared</info>');
            } else {
                $output->writeln('<error>Failed to clear FPC</error>');
            }
        }
        return 0;
    }

    protected function isEnterpriseFullPageCachePresent()
    {
        $isModuleEnabled = Mage::helper('core')->isModuleEnabled('Enterprise_PageCache');
        return $this->_magentoEnterprise && $isModuleEnabled && version_compare(Mage::getVersion(), '1.11.0.0', '>=');
    }
}
