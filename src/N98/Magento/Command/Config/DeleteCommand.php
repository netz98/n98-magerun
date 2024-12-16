<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use Mage;
use Mage_Core_Model_Resource_Db_Collection_Abstract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Delete config command
 *
 * @package N98\Magento\Command\Config
 */
class DeleteCommand extends AbstractConfigCommand
{
    protected function configure(): void
    {
        $this
            ->setName('config:delete')
            ->setDescription('Deletes a store config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_OPTIONAL,
                "The config value's scope (default, websites, stores)",
                'default',
            )
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, "The config value's scope ID", '0')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                "Allow deletion of non-standard scope-id's for websites and stores",
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all entries by path')
        ;
    }

    public function getHelp(): string
    {
        return <<<HELP
To delete all entries of a path you can set the option --all.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);

        if (!$this->initMagento()) {
            return Command::FAILURE;
        }

        $deleted = [];

        $allowZeroScope = $input->getOption('force');

        $scope      = $this->_validateScopeParam($input->getOption('scope'));
        $scopeId    = (int) $this->_convertScopeIdParam($scope, $input->getOption('scope-id'), $allowZeroScope);

        $path       = $input->getArgument('path');
        $paths      = false !== strstr($path, '*') ? $this->expandPathPattern($input, $path) : [$path];

        foreach ($paths as $path) {
            $deleted = array_merge($deleted, $this->_deletePath($input, $path, $scopeId));
        }

        if ($deleted !== []) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['Deleted Path', 'Scope', 'Scope-ID'])
                ->setRows($deleted)
                ->render($output);
        }

        return Command::SUCCESS;
    }

    protected function _deletePath(InputInterface $input, string $path, int $scopeId): array
    {
        $deleted = [];
        $force = $input->getOption('force');
        if ($input->getOption('all')) {
            // Default
            $deleted[] = $this->deleteConfigEntry($path, 'default', 0);

            // Delete websites
            foreach (Mage::app()->getWebsites($force) as $website) {
                $deleted[] = $this->deleteConfigEntry($path, 'websites', (int) $website->getId());
            }

            // Delete stores
            foreach (Mage::app()->getStores($force) as $store) {
                $deleted[] = $this->deleteConfigEntry($path, 'stores', (int) $store->getId());
            }
        } else {
            $deleted[] = $this->deleteConfigEntry($path, $input->getOption('scope'), $scopeId);
        }

        return $deleted;
    }

    private function expandPathPattern(InputInterface $input, string $pattern): array
    {
        $paths = [];

        $collection = $this->_getConfigDataModel()->getCollection();

        $likePattern = str_replace('*', '%', $pattern);
        $collection->addFieldToFilter('path', ['like' => $likePattern]);

        if ($scope = $input->getOption('scope')) {
            $collection->addFieldToFilter('scope', ['eq' => $scope]);
        }

        $collection->addOrder('path', 'ASC');

        foreach ($collection as $item) {
            $paths[] = $item->getPath();
        }

        return $paths;
    }

    /**
     * Delete concrete entry from config table specified by path, scope and scope-id
     */
    private function deleteConfigEntry(string $path, string $scope, int $scopeId): array
    {
        $mageCoreModelConfig = $this->_getConfigModel();

        $mageCoreModelConfig->deleteConfig(
            $path,
            $scope,
            $scopeId,
        );

        return [
            'path'    => $path,
            'scope'   => $scope,
            'scopeId' => $scopeId,
        ];
    }
}
