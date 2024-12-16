<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\EmailTemplate;

use Mage;
use Mage_Adminhtml_Model_Email_Template;
use Mage_Core_Model_Email_Template;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('dev:email-template:usage')
            ->setDescription('Display database transactional email template usage')
            ->addFormatOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        $this->initMagento();
        $templates = $this->findEmailTemplates();

        if ($templates !== []) {
            $tableHelper = $this->getTableHelper();
            $tableHelper
                ->setHeaders(['id', 'Name', 'Scope', 'Scope Id', 'Path'])
                ->renderByFormat($output, $templates, $input->getOption('format'));
        } else {
            $output->writeln('No transactional email templates stored in the database.');
        }

        return Command::SUCCESS;
    }

    protected function findEmailTemplates(): array
    {
        /** @var Mage_Adminhtml_Model_Email_Template $model */
        $model = Mage::getModel('adminhtml/email_template');
        $templates = $model->getCollection();

        $return = [];

        /** @var Mage_Core_Model_Email_Template[] $templates */
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

            if ((is_countable($configPaths) ? count($configPaths) : 0) === 0) {
                $configPaths[] = [
                    'scope'    => 'Unused',
                    'scope_id' => 'Unused',
                    'path'     => 'Unused',
                ];
            }

            foreach ($configPaths as $configPath) {
                $return[] = [
                    'id'            => $this->sanitizeEmailProperty((string) $template->getId()),
                    'Template Code' => $this->sanitizeEmailProperty($template->getTemplateCode()),
                    'Scope'         => $this->sanitizeEmailProperty($configPath['scope']),
                    'Scope Id'      => $this->sanitizeEmailProperty($configPath['scope_id']),
                    'Path'          => $this->sanitizeEmailProperty($configPath['path']),
                ];
            }
        }

        return $return;
    }

    /**
     * @param string $input Module property to be sanitized
     */
    private function sanitizeEmailProperty(string $input): string
    {
        return trim($input);
    }
}
