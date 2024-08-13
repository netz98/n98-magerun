<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\EmailTemplate;

use Mage;
use Mage_Adminhtml_Model_Email_Template;
use Mage_Core_Exception;
use Mage_Core_Model_Template;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandFormatable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;

/**
 * List email template usage command
 *
 * @package N98\Magento\Command\Developer\EmailTemplate
 *
 * @author Mike Parkin (https://github.com/MikeParkin)
 * @author Tom Klingenberg (https://github.com/ktomk)
 */
class UsageCommand extends AbstractMagentoCommand implements CommandFormatable
{
    /**
     * @var string
     */
    protected static $defaultName = 'dev:email-template:usage';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Display database transactional email template usage.';

    /**
     * @var string
     */
    protected static string $noResultMessage = 'No transactional email templates stored in the database.';

    /**
     * {@inheritDoc}
     */
    public function getSectionTitle(InputInterface $input, OutputInterface $output): string
    {
        return 'Transactional email templates';
    }

    /**
     * {@inheritDoc}
     */
    public function getListHeader(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'Name', 'Scope', 'Scope Id', 'Path'];
    }

    /**
     * {@inheritDoc}
     * @throws Mage_Core_Exception
     */
    public function getListData(InputInterface $input, OutputInterface $output): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        $this->data = $this->findEmailTemplates();
        return $this->data;
    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function findEmailTemplates(): array
    {
        /** @var Mage_Core_Model_Template[] $templates */
        $templates = Mage::getModel('adminhtml/email_template')->getCollection();

        $return = [];

        foreach ($templates as $template) {
            /*
             * Some modules overload the template class so that the method getSystemConfigPathsWhereUsedCurrently
             * is not available, this is a workaround for that
             */
            if (!method_exists($template, 'getSystemConfigPathsWhereUsedCurrently')) {
                $instance = new Mage_Adminhtml_Model_Email_Template();
                $template = $instance->load($template->getId());
            }

            $configPaths = $template->getSystemConfigPathsWhereUsedCurrently();

            if (!(is_countable($configPaths) ? count($configPaths) : 0)) {
                $configPaths[] = [
                    'scope'    => 'Unused',
                    'scope_id' => 'Unused',
                    'path'     => 'Unused'
                ];
            }

            foreach ($configPaths as $configPath) {
                $return[] = [
                    'id'            => $this->sanitizeEmailProperty($template->getId()),
                    'Template Code' => $this->sanitizeEmailProperty($template->getTemplateCode()),
                    'Scope'         => $this->sanitizeEmailProperty($configPath['scope']),
                    'Scope Id'      => $this->sanitizeEmailProperty($configPath['scope_id']),
                    'Path'          => $this->sanitizeEmailProperty($configPath['path'])
                ];
            }
        }

        return $return;
    }

    /**
     * @param string $input Module property to be sanitized
     *
     * @return string
     */
    private function sanitizeEmailProperty(string $input): string
    {
        return trim($input);
    }
}
