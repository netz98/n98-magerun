<?php

namespace N98\Magento\Command\Config;

use Mage;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:delete')
            ->setDescription('Deletes a store config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope (default, websites, stores)',
                'default'
            )
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID', '0')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Allow deletion of non-standard scope-id\'s for websites and stores'
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all entries by path')
        ;

        $help = <<<HELP
To delete all entries of a path you can set the option --all.
HELP;
        $this->setHelp($help);
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

        if (!$this->initMagento()) {
            return 0;
        }

        $deleted = [];

        $allowZeroScope = $input->getOption('force');

        $scope = $this->_validateScopeParam($input->getOption('scope'));
        $scopeId = $this->_convertScopeIdParam($scope, $input->getOption('scope-id'), $allowZeroScope);

        $path = $input->getArgument('path');

        if (false !== strstr($path, '*')) {
            $paths = $this->expandPathPattern($input, $path);
        } else {
            $paths = [$path];
        }

        foreach ($paths as $path) {
            $deleted = array_merge($deleted, $this->_deletePath($input, $path, $scopeId));
        }

        if (count($deleted) > 0) {
            /* @var TableHelper $tableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders(['Deleted Path', 'Scope', 'Scope-ID'])
                ->setRows($deleted)
                ->render($output);
        }
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param string $path
     * @param string $scopeId
     *
     * @return array
     */
    protected function _deletePath(InputInterface $input, $path, $scopeId)
    {
        $deleted = [];
        $force = $input->getOption('force');
        if ($input->getOption('all')) {
            // Default
            $deleted[] = $this->deleteConfigEntry($path, 'default', 0);

            // Delete websites
            foreach (Mage::app()->getWebsites($force) as $website) {
                $deleted[] = $this->deleteConfigEntry($path, 'websites', $website->getId());
            }

            // Delete stores
            foreach (Mage::app()->getStores($force) as $store) {
                $deleted[] = $this->deleteConfigEntry($path, 'stores', $store->getId());
            }
        } else {
            $deleted[] = $this->deleteConfigEntry($path, $input->getOption('scope'), $scopeId);
        }

        return $deleted;
    }

    /**
     * @param string $pattern
     * @return array
     */
    private function expandPathPattern($input, $pattern)
    {
        $paths = [];

        /* @var \Mage_Core_Model_Resource_Db_Collection_Abstract $collection */
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
     *
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     *
     * @return array
     */
    private function deleteConfigEntry($path, $scope, $scopeId)
    {
        $config = $this->_getConfigModel();

        $config->deleteConfig(
            $path,
            $scope,
            $scopeId
        );

        return ['path'    => $path, 'scope'   => $scope, 'scopeId' => $scopeId];
    }
}
