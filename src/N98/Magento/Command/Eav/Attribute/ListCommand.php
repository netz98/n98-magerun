<?php

namespace N98\Magento\Command\Eav\Attribute;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class ListCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
      $this
          ->setName('eav:attribute:list')
          ->setDescription('Lists all EAV attributes')
          ->addOption('filter-type', null, InputOption::VALUE_OPTIONAL, 'Filter attributes by entity type')
          ->addOption('add-source', null, InputOption::VALUE_NONE, 'Add source models to list')
          ->addOption('add-backend', null, InputOption::VALUE_NONE, 'Add backend type to list')
          ->addOption(
              'format',
              null,
              InputOption::VALUE_OPTIONAL,
              'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
          )
      ;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $table = array();
            $attributesCollection = \Mage::getResourceModel('eav/entity_attribute_collection');
            $attributesCollection->setOrder('attribute_code', 'asc');
            foreach ($attributesCollection as $attribute) {
                $entityType = $this->_getEntityType($attribute);

                /**
                 * Filter by type
                 */
                if ($input->getOption('filter-type') !== null
                    && $input->getOption('filter-type') !== $entityType
                ) {
                    continue;
                }

                $row = array();
                $row[] = $attribute->getAttributeCode();
                $row[] = $attribute->getId();
                $row[] = $entityType;
                $row[] = $attribute->getFrontendLabel();

                if ($input->getOption('add-source'))  {
                    $row[] = $attribute->getSourceModel() ? $attribute->getSourceModel() : '';
                }
                if ($input->getOption('add-backend'))  {
                    $row[] = $attribute->getBackendType();
                }

                $table[] = $row;
            }

            $headers = array();
            $headers[] = 'code';
            $headers[] = 'id';
            $headers[] = 'entity_type';
            $headers[] = 'label';
            if ($input->getOption('add-source'))  {
                $headers[] = 'source';
            }
            if ($input->getOption('add-backend'))  {
                $headers[] = 'backend_type';
            }

            $this->getHelper('table')
                ->setHeaders($headers)
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }

    /**
     * @param $attribute
     * @return null|string
     */
    protected function _getEntityType($attribute)
    {
        $entityTypeCode = '';
        try {
            $entityType = $attribute->getEntityType();
            if ($entityType instanceof \Mage_Eav_Model_Entity_Type) {
                $entityTypeCode = $entityType->getEntityTypeCode();
            }
        } catch (\Exception $e) {
        }

        return $entityTypeCode;
    }
}