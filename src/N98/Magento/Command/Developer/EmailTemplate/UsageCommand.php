<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\EmailTemplate;

use Mage;
use Mage_Adminhtml_Model_Email_Template;
use Mage_Core_Exception;
use Mage_Core_Model_Template;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package N98\Magento\Command\Developer\EmailTemplate
 *
 * @author Mike Parkin <https://github.com/MikeParkin>
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
class UsageCommand extends AbstractCommand implements CommandDataInterface
{
    public const NO_DATA_MESSAGE = 'No transactional email templates stored in the database.';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:email-template:usage';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Display database transactional email template usage.';

    /**
     * {@inheritDoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->detectMagento($output);
        $this->initMagento();
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        return ['id', 'Name', 'Scope', 'Scope Id', 'Path'];
    }

    /**
     * {@inheritdoc}
     * @throws Mage_Core_Exception
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            /** @var Mage_Core_Model_Template[] $templates */
            $templates = Mage::getModel('adminhtml/email_template')->getCollection();

            foreach ($templates as $template) {
                // Some modules overload the template class so that the method getSystemConfigPathsWhereUsedCurrently
                // is not available, this is a workaround for that
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
                    $this->data[] = [
                        $this->sanitizeEmailProperty($template->getId()),
                        $this->sanitizeEmailProperty($template->getTemplateCode()),
                        $this->sanitizeEmailProperty($configPath['scope']),
                        $this->sanitizeEmailProperty($configPath['scope_id']),
                        $this->sanitizeEmailProperty($configPath['path'])
                    ];
                }
            }
        }

        return $this->data;
    }

    /**
     * @param string $input Module property to be sanitized
     * @return string
     */
    private function sanitizeEmailProperty(string $input): string
    {
        return trim($input);
    }
}
