<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use Exception;
use Mage;
use Mage_Eav_Model_Entity_Type;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractMagentoCommand implements AbstractMagentoCommandFormatInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'EAV attributes';

    public const COMMAND_OPTION_FILTER_TYPE = 'filter-type';

    public const COMMAND_OPTION_ADD_SOURCE = 'add-source';

    public const COMMAND_OPTION_ADD_BACKEND = 'add-backend';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'eav:attribute:list';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Lists all EAV attributes.';

    protected function configure()
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_FILTER_TYPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter attributes by entity type'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_SOURCE,
                null,
                InputOption::VALUE_NONE,
                'Add source models to list'
            )
            ->addOption(
                self::COMMAND_OPTION_ADD_BACKEND,
                null,
                InputOption::VALUE_NONE,
                'Add backend type to list'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $attributesCollection = Mage::getResourceModel('eav/entity_attribute_collection');
            $attributesCollection->setOrder('attribute_code', 'asc');
            foreach ($attributesCollection as $attribute) {
                $entityType = $this->_getEntityType($attribute);

                /**
                 * Filter by type
                 */
                if ($input->getOption(self::COMMAND_OPTION_FILTER_TYPE) !== null
                    && $input->getOption(self::COMMAND_OPTION_FILTER_TYPE) !== $entityType
                ) {
                    continue;
                }

                $row = [];
                $row['code']        = $attribute->getAttributeCode();
                $row['id']          = $attribute->getId();
                $row['entity_type'] = $entityType;
                $row['label']       = $attribute->getFrontendLabel();

                if ($input->getOption(self::COMMAND_OPTION_ADD_SOURCE)) {
                    $row['source'] = $attribute->getSourceModel() ?: '';
                }
                if ($input->getOption(self::COMMAND_OPTION_ADD_BACKEND)) {
                    $row['backend_type'] = $attribute->getBackendType();
                }

                $this->data[] = $row;
            }
        }

        return $this->data;
    }

    /**
     * @param $attribute
     * @return null|string
     */
    protected function _getEntityType($attribute): ?string
    {
        $entityTypeCode = '';
        try {
            $entityType = $attribute->getEntityType();
            if ($entityType instanceof Mage_Eav_Model_Entity_Type) {
                $entityTypeCode = $entityType->getEntityTypeCode();
            }
        } catch (Exception $e) {
        }

        return $entityTypeCode;
    }
}
