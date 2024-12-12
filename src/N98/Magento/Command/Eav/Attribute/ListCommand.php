<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use Exception;
use Mage;
use Mage_Eav_Model_Entity_Type;
use Mage_Eav_Model_Resource_Entity_Attribute_Collection;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List EAV attributes command
 *
 * @package N98\Magento\Command\Eav\Attribute
 */
class ListCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('eav:attribute:list')
            ->setDescription('Lists all EAV attributes')
            ->addOption('filter-type', null, InputOption::VALUE_OPTIONAL, 'Filter attributes by entity type')
            ->addOption('add-source', null, InputOption::VALUE_NONE, 'Add source models to list')
            ->addOption('add-backend', null, InputOption::VALUE_NONE, 'Add backend type to list')
            ->addFormatOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $table = [];
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributesCollection */
        $attributesCollection = Mage::getResourceModel('eav/entity_attribute_collection');
        $attributesCollection->setOrder('attribute_code', 'asc');
        foreach ($attributesCollection as $attributeCollection) {
            $entityType = $this->_getEntityType($attributeCollection);

            /**
             * Filter by type
             */
            if ($input->getOption('filter-type') !== null
                && $input->getOption('filter-type') !== $entityType
            ) {
                continue;
            }

            $row = [];
            $row[] = $attributeCollection->getAttributeCode();
            $row[] = $attributeCollection->getId();
            $row[] = $entityType;
            $row[] = $attributeCollection->getFrontendLabel();

            if ($input->getOption('add-source')) {
                $row[] = $attributeCollection->getSourceModel() ?: '';
            }

            if ($input->getOption('add-backend')) {
                $row[] = $attributeCollection->getBackendType();
            }

            $table[] = $row;
        }

        $headers = [];
        $headers[] = 'code';
        $headers[] = 'id';
        $headers[] = 'entity_type';
        $headers[] = 'label';
        if ($input->getOption('add-source')) {
            $headers[] = 'source';
        }

        if ($input->getOption('add-backend')) {
            $headers[] = 'backend_type';
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders($headers)
            ->renderByFormat($output, $table, $input->getOption('format'));

        return Command::SUCCESS;
    }

    /**
     * @param $attribute
     */
    protected function _getEntityType($attribute): ?string
    {
        $entityTypeCode = '';
        try {
            $entityType = $attribute->getEntityType();
            if ($entityType instanceof Mage_Eav_Model_Entity_Type) {
                $entityTypeCode = $entityType->getEntityTypeCode();
            }
        } catch (Exception $exception) {
        }

        return $entityTypeCode;
    }
}
