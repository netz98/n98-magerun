<?php

namespace N98\Magento\Command\Indexer;

use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMviewCommand extends AbstractMviewIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:list:mview')
            ->setDescription('Lists all magento mview indexes')
            ->addFormatOption()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return <<<HELP
Lists all Magento mview indexers of current installation.
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
        if (!$this->initMagento()) {
            return 0;
        }

        $table = [];
        foreach ($this->getMetaDataCollection() as $index) {
            $changelogName = $index->getData('changelog_name');
            $versionId = $index->getData('version_id');
            $pendingCount = $this->getPendingChangelogsCount($changelogName, $versionId);
            if ($pendingCount > 0) {
                $pendingString = "<error>$pendingCount</error>";
            } else {
                $pendingString = "<info>$pendingCount</info>";
            }

            $table[] = [$index->getData('table_name'), $index->getData('view_name'), $changelogName, $index->getData('status'), $versionId, $pendingString];
        }

        /* @var TableHelper $tableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(
                ['table_name', 'view_name', 'changelog_name', 'status', 'version_id', 'entries pending reindex']
            )
            ->renderByFormat($output, $table, $input->getOption('format'));
        return 0;
    }

    /**
     * @param $tableName
     * @param $currentVersionId
     * @return int
     */
    protected function getPendingChangelogsCount($tableName, $currentVersionId)
    {
        /** @var \Mage_Core_Model_Resource $resource */
        $resource = $this->_getSingleton('core/resource', '\Mage_Core_Model_Resource');
        $readConnection = $resource->getConnection('core_read');

        $select = $readConnection->select()
            ->from($tableName, ['count(*)'])
            ->where("version_id > ?", $currentVersionId);
        $todoCount = $readConnection->fetchOne($select);

        return (int) $todoCount;
    }
}
