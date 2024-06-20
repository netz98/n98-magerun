<?php

declare(strict_types=1);

namespace N98\Magento\Command\Config;

use Mage;
use Mage_Core_Exception;
use Mage_Core_Model_Config_Data;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Config delete command
 *
 * @package N98\Magento\Command\Config
 */
class DeleteCommand extends AbstractConfigCommand
{
    public const COMMAND_OPTION_FORCE = 'force';

    public const COMMAND_OPTION_ALL = 'all';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'config:delete';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Deletes a store config item.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_PATH,
                InputArgument::REQUIRED,
                'The config path'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope (default, websites, stores)',
                'default'
            )
            ->addOption(
                self::COMMAND_OPTION_SCOPE_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope ID', '0'
            )
            ->addOption(
                self::COMMAND_OPTION_FORCE,
                null,
                InputOption::VALUE_NONE,
                'Allow deletion of non-standard scope-id\'s for websites and stores'
            )
            ->addOption(
                self::COMMAND_OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Delete all entries by path'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
To delete all entries of a path you can set the option --all.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $deleted = [];

        /** @var bool $allowZeroScope */
        $allowZeroScope = $input->getOption(self::COMMAND_OPTION_FORCE);

        /** @var string $scope */
        $scope = $input->getOption(self::COMMAND_OPTION_SCOPE);
        $scope = $this->_validateScopeParam($scope);

        /** @var string $scopeId */
        $scopeId = $input->getOption(self::COMMAND_OPTION_SCOPE_ID);
        $scopeId = (int)$this->_convertScopeIdParam($scope, $scopeId, $allowZeroScope);

        /** @var string $path */
        $path = $input->getArgument(self::COMMAND_ARGUMENT_PATH);

        if (false !== strstr($path, '*')) {
            $paths = $this->expandPathPattern($input, $path);
        } else {
            $paths = [$path];
        }

        foreach ($paths as $path) {
            $deleted = array_merge($deleted, $this->_deletePath($input, $path, $scopeId));
        }

        if (count($deleted) > 0) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['Deleted Path', 'Scope', 'Scope-ID'])
                ->setRows($deleted)
                ->render($output);
        }

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param string $path
     * @param int $scopeId
     * @return array<int, array<string, string|int>>
     */
    protected function _deletePath(InputInterface $input, string $path, int $scopeId): array
    {
        $deleted = [];
        /** @var bool $force */
        $force = $input->getOption(self::COMMAND_OPTION_FORCE);
        if ($input->getOption(self::COMMAND_OPTION_ALL)) {
            // Default
            $deleted[] = $this->deleteConfigEntry($path, 'default', 0);

            // Delete websites
            foreach (Mage::app()->getWebsites($force) as $website) {
                $deleted[] = $this->deleteConfigEntry($path, 'websites', (int)$website->getId());
            }

            // Delete stores
            foreach (Mage::app()->getStores($force) as $store) {
                $deleted[] = $this->deleteConfigEntry($path, 'stores', $store->getId());
            }
        } else {
            /** @var string $scope */
            $scope = $input->getOption(self::COMMAND_OPTION_SCOPE);
            $deleted[] = $this->deleteConfigEntry($path, $scope, $scopeId);
        }

        return $deleted;
    }

    /**
     * @param InputInterface $input
     * @param string $pattern
     * @return array<int, string>
     * @throws Mage_Core_Exception
     */
    private function expandPathPattern(InputInterface $input, string $pattern): array
    {
        $paths = [];

        $collection = $this->_getConfigDataModel()->getCollection();

        $likePattern = str_replace('*', '%', $pattern);
        $collection->addFieldToFilter('path', ['like' => $likePattern]);

        if ($scope = $input->getOption(self::COMMAND_OPTION_SCOPE)) {
            $collection->addFieldToFilter('scope', ['eq' => $scope]);
        }
        $collection->addOrder('path', 'ASC');

        /** @var Mage_Core_Model_Config_Data $item */
        foreach ($collection as $item) {
            $paths[] = $item->getPath();
        }

        return $paths;
    }

    /**
     * Delete concrete entry from config table specified by path, scope and scope-id
     *
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return array<string, string|int>
     */
    private function deleteConfigEntry(string $path, string $scope, int $scopeId): array
    {
        $config = $this->_getConfigModel();
        $config->deleteConfig($path, $scope, $scopeId);

        return [
            'path'    => $path,
            'scope'   => $scope,
            'scopeId' => $scopeId
        ];
    }
}
