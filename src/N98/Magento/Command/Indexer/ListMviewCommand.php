<?php

namespace N98\Magento\Command\Indexer;

use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListMviewCommand extends AbstractMviewIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:list:mview')
            ->setDescription('Lists all magento mview indexes')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;

        $help = <<<HELP
Lists all Magento mview indexers of current installation.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $table = array();
        foreach ($this->getMetaDataCollection() as $index) {
            $changelogName = $index->getData('changelog_name');
            $versionId = $index->getData('version_id');
            $pendingCount = $this->getPendingChangelogsCount($changelogName, $versionId);
            if ($pendingCount > 0) {
                $pendingString = "<error>$pendingCount</error>";
            } else {
                $pendingString = "<info>$pendingCount</info>";
            }

            $table[] = array(
                $index->getData('table_name'),
                $index->getData('view_name'),
                $changelogName,
                $index->getData('status'),
                $versionId,
                $pendingString,
            );
        }

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $tableHelper
            ->setHeaders(
                array(
                    'table_name',
                    'view_name',
                    'changelog_name',
                    'status',
                    'version_id',
                    'entries pending reindex',
                )
            )
            ->renderByFormat($output, $table, $input->getOption('format'));
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
            ->from($tableName, array('count(*)'))
            ->where("version_id > ?", $currentVersionId);
        $todoCount = $readConnection->fetchOne($select);

        return (int) $todoCount;
    }
}
