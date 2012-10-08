<?php

namespace N98\Magento\Command\Cms\Page;

use N98\Magento\Command\AbstractMagentoCommand;

use \Mage_Cms_Model_Page;
use \Enterprise_Cms_Model_Page_Revision;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class PublishCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('cms:page:publish')
            ->addArgument('page_id', InputArgument::REQUIRED, 'Even if the Revision ID is unique, we require the page id for security reasons')
            ->addArgument('revision_id', InputArgument::REQUIRED, 'Revision ID (the ID, not the sequential number)')
            ->setDescription('Publish a CMS page revision (Enterprise only)')
        ;
    }

    /**
     * @return \Mage_Cms_Model_Page
     */
    protected function _getPageModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Mage_Cms_Model_Page');
        } else {
            return \Mage::getModel('cms/page');
        }
    }

    /**
     * @return \Enterprise_Cms_Model_Page_Revision
     */
    protected function _getPageRevisionModel()
    {
        if ($this->_magentoMajorVersion == self::MAGENTO_MAJOR_VERSION_2) {
            return \Mage::getModel('Enterprise_Cms_Model_Page_Revision');
        } else {
            return \Mage::getModel('enterprise_cms/page_revision');
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->requireEnterprise($output);
        if ($this->initMagento()) {
            $this->writeSection($output, 'CMS Publish');
            $pageId = $input->getArgument('page_id');
            $revisionId = $input->getArgument('revision_id');

            $revision = $this->_getPageRevisionModel()->load($revisionId);

            if (!$revision->getId()) {
                $output->writeln('<error>Revision was not found</error>');
                return;
            }

            if ($revision->getPageId() != $pageId) {
                $output->writeln(sprintf('<error>Revision\'s page id (%d) does not match the given page id</error>', $revision->getPageId()));
                return;
            }
            $revision->publish();
            $output->writeln('<info>Page published</info>');

        }
    }
}