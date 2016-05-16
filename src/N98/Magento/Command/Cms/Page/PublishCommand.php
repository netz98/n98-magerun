<?php

namespace N98\Magento\Command\Cms\Page;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PublishCommand
 *
 * Only testable with closed source enterprise edition
 *
 * @codeCoverageIgnore
 * @package N98\Magento\Command\Cms\Page
 */
class PublishCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('cms:page:publish')
            ->addArgument(
                'page_id',
                InputArgument::REQUIRED,
                'Even if the Revision ID is unique, we require the page id for security reasons'
            )
            ->addArgument('revision_id', InputArgument::REQUIRED, 'Revision ID (the ID, not the sequential number)')
            ->setDescription('Publish a CMS page revision (Enterprise only)');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isMagentoEnterprise();
    }

    /**
     * @return \Mage_Cms_Model_Page
     */
    protected function _getPageModel()
    {
        return $this->_getModel('cms/page', 'Mage_Cms_Model_Page');
    }

    /**
     * @return \Enterprise_Cms_Model_Page_Revision
     */
    protected function _getPageRevisionModel()
    {
        return $this->_getModel('enterprise_cms/page_revision', '\Enterprise_Cms_Model_Page_Revision');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->requireEnterprise($output);
        if (!$this->initMagento()) {
            return;
        }

        $this->writeSection($output, 'CMS Publish');
        $pageId = $input->getArgument('page_id');
        $revisionId = $input->getArgument('revision_id');

        $revision = $this->_getPageRevisionModel()->load($revisionId);

        if (!$revision->getId()) {
            $output->writeln('<error>Revision was not found</error>');

            return;
        }

        if ($revision->getPageId() != $pageId) {
            $output->writeln(sprintf(
                '<error>Revision\'s page id (%d) does not match the given page id</error>',
                $revision->getPageId()
            ));

            return;
        }
        $revision->publish();
        $output->writeln('<info>Page published</info>');
    }
}
