<?php

namespace N98\Magento\Command\Developer\EmailTemplate;

use Mage;
use Mage_Adminhtml_Model_Email_Template;
use Mage_Core_Model_Template;
use N98\Magento\Command\AbstractMagentoCommand;
use Path;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List email template usage command
 *
 * @package N98\Magento\Command\Developer\EmailTemplate
 *
 * @author Mike Parkin (https://github.com/MikeParkin)
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class UsageCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:email-template:usage')
            ->setDescription('Display database transactional email template usage')
            ->addFormatOption();
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
        $this->initMagento();
        $templates = $this->findEmailTemplates();

        if (!empty($templates)) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['id', 'Name', 'Scope', 'Scope Id', Path::class])
                ->renderByFormat($output, $templates, $input->getOption('format'));
        } else {
            $output->writeln("No transactional email templates stored in the database.");
        }
        return 0;
    }

    protected function findEmailTemplates()
    {
        /** @var Mage_Core_Model_Template[] $templates */
        $templates = Mage::getModel('adminhtml/email_template')->getCollection();

        $return = [];

        foreach ($templates as $template) {

            /**
             * Some modules overload the template class so that the method getSystemConfigPathsWhereUsedCurrently
             * is not available, this is a workaround for that
             */
            if (!method_exists($template, 'getSystemConfigPathsWhereUsedCurrently')) {
                $instance = new Mage_Adminhtml_Model_Email_Template();
                $template = $instance->load($template->getId());
            }

            $configPaths = $template->getSystemConfigPathsWhereUsedCurrently();

            if (!(is_countable($configPaths) ? count($configPaths) : 0)) {
                $configPaths[] = ['scope'    => 'Unused', 'scope_id' => 'Unused', 'path'     => 'Unused'];
            }

            foreach ($configPaths as $configPath) {
                $return[] = ['id'            => $this->sanitizeEmailProperty($template->getId()), 'Template Code' => $this->sanitizeEmailProperty($template->getTemplateCode()), 'Scope'         => $this->sanitizeEmailProperty($configPath['scope']), 'Scope Id'      => $this->sanitizeEmailProperty($configPath['scope_id']), Path::class          => $this->sanitizeEmailProperty($configPath['path'])];
            }
        }

        return $return;
    }

    /**
     * @param string $input Module property to be sanitized
     *
     * @return string
     */
    private function sanitizeEmailProperty($input)
    {
        return trim($input);
    }
}
