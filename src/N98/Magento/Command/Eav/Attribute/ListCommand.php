<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use Exception;
use Mage;
use Mage_Eav_Model_Entity_Attribute;
use Mage_Eav_Model_Entity_Type;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\CommandDataInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * EAV attribute list command
 *
 * @package N98\Magento\Command\Eav\Attribute
 */
class ListCommand extends AbstractCommand implements CommandDataInterface
{
    protected const COMMAND_SECTION_TITLE_TEXT = 'EAV attributes';

    public const COMMAND_OPTION_FILTER_TYPE = 'filter-type';

    public const COMMAND_OPTION_ADD_SOURCE = 'add-source';

    public const COMMAND_OPTION_ADD_BACKEND = 'add-backend';

    /**
     * @var string
     */
    protected static $defaultName = 'eav:attribute:list';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Lists all EAV attributes.';

    protected function configure(): void
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
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function setData(InputInterface $input,OutputInterface $output) : void
    {
        $this->data = [];

        $attributesCollection = Mage::getResourceModel('eav/entity_attribute_collection');
        $attributesCollection->setOrder('attribute_code', 'asc');
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
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
            $row['Code']        = $attribute->getAttributeCode();
            $row['ID']          = $attribute->getId();
            $row['Entity type'] = $entityType;
            $row['Label']       = $attribute->getFrontendLabel();

            if ($input->getOption(self::COMMAND_OPTION_ADD_SOURCE)) {
                $row['Source'] = $attribute->getSourceModel() ?: '';
            }
            if ($input->getOption(self::COMMAND_OPTION_ADD_BACKEND)) {
                $row['Backend type'] = $attribute->getBackendType();
            }

            $this->data[] = $row;
        }

    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return null|string
     */
    protected function _getEntityType(Mage_Eav_Model_Entity_Attribute $attribute): ?string
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
