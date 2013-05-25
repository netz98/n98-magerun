<?php
/**
 * Ported attribute migration script from:
 *
 * https://github.com/astorm/Pulsestorm/blob/master/magento-create-setup.php
 *
 * It creates attribute script for existing attribute
 *
 * Originally created by Alan Storm
 *
 * @author Dusan Lukic <ldusan84@gmail.com>
 */

namespace N98\Magento\Command\Developer\Setup\Script;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class AttributeCommand extends AbstractMagentoCommand
{
    protected $read;

    protected function configure()
    {
        $this
            ->setName('dev:setup:script:attribute')
            ->addArgument('code', InputArgument::REQUIRED, 'Attribute code')
            ->setDescription('Creates attribute script for a given attribute code');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if ($this->initMagento()) {

            // store database connection
            $this->read = $this->_getModel('core/resource', 'Mage_Core_Model_Resource')->getConnection('core_read');

            $code = $input->getArgument('code');
            // load the existing attribute model
            $attribute = $this->_getModel('catalog/resource_eav_attribute', 'Mage_Catalog_Model_Resource_Eav_Attribute')
                ->loadByCode('catalog_product', $code);

            if (!$attribute->getId()) {
                $output->writeln("<error>Could not find attribute " . $code . "</error>");

                return;
            }

            // get a map of "real attribute properties to properties used in setup resource array
            $realToSetupKeyLegend = $this->_getKeyLegend();

            // swap keys from above
            $data = $attribute->getData();
            $keysLegend = array_keys($realToSetupKeyLegend);
            $newData    = array();

            foreach ($data as $key=>$value) {
                if (in_array($key, $keysLegend)) {
                    $key = $realToSetupKeyLegend[$key];
                }
                $newData[$key] = $value;
            }

            // unset items from model that we don't need and would be discarded by
            // resource script anyways
            $attributeCode = $newData['attribute_code'];
            unset($newData['attribute_id']);
            unset($newData['attribute_code']);
            unset($newData['entity_type_id']);

            // chuck a few warnings out there for things that were a little murky
            if ($newData['attribute_model']) {
                $output->writeln("<warning>WARNING, value detected in attribute_model.  We've never seen a value there before and this script doesn't handle it.  Caution, etc. </warning>");
            }

            if ($newData['is_used_for_price_rules']) {
                $output->writeln("<error>WARNING, non false value detected in is_used_for_price_rules.  The setup resource migration scripts may not support this (per 1.7.0.1)</error>");
            }

            //load values for attributes (if any exist)
            $newData['option'] = $this->_getOptionArrayForAttribute($attribute);

            //get text for script
            $arrayCode = var_export($newData, true);

            //generate script using simple string concatenation, making
            //a single tear fall down the cheek of a CS professor
            $script = "<?php
\$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

\$attr = $arrayCode;
\$setup->addAttribute('catalog_product', '$attributeCode', \$attr);
            ";

            $attributeLabels = $this->_getAttributeLabels($attribute);
            $attributeLabelsCode = var_export($attributeLabels, true);

            $labelsScript = "
\$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', '$attributeCode');
\$attribute->setStoreLabels($attributeLabelsCode);
\$attribute->save()
";

            $script .= $labelsScript;

            $output->write($script);
        }
    }

    /**
     * Gets attribute labels from database
     * 
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * 
     * @return array
     */
    protected function _getAttributeLabels($attribute)
    {
        $select = $this->read->select()
            ->from('eav_attribute_label')
            ->where('attribute_id = ?', $attribute->getId());

        $query = $select->query();

        $attributeLabels = array();
        foreach ($query->fetchAll() as $row) {
            $attributeLabels[$row['store_id']] = $row['value'];
        }

        return $attributeLabels;
    }

    /**
     * Gets attribute options from database
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     *
     * @return array
     */
    protected function _getOptionArrayForAttribute($attribute)
    {
        $select = $this->read->select()
            ->from('eav_attribute_option')
            ->join('eav_attribute_option_value', 'eav_attribute_option.option_id=eav_attribute_option_value.option_id')
            ->where('attribute_id = ?',$attribute->getId())
            ->where('store_id = 0')
            ->order('eav_attribute_option_value.option_id');

        $query = $select->query();

        $values = array();
        foreach ($query->fetchAll() as $row) {
            $values[] = $row['value'];
        }

        return array('values' => $values);
    }

    /**
     * Gets key legend for catalog product attribute
     *
     * @return array
     */
    protected function _getKeyLegend()
    {
        return array(
            //catalog
            'frontend_input_renderer'       => 'input_renderer',
            'is_global'                     => 'global',
            'is_visible'                    => 'visible',
            'is_searchable'                 => 'searchable',
            'is_filterable'                 => 'filterable',
            'is_comparable'                 => 'comparable',
            'is_visible_on_front'           => 'visible_on_front',
            'is_wysiwyg_enabled'            => 'wysiwyg_enabled',
            'is_visible_in_advanced_search' => 'visible_in_advanced_search',
            'is_filterable_in_search'       => 'filterable_in_search',
            'is_used_for_promo_rules'       => 'used_for_promo_rules',
            'backend_model'                 => 'backend',
            'backend_type'                  => 'type',
            'backend_table'                 => 'table',
            'frontend_model'                => 'frontend',
            'frontend_input'                => 'input',
            'frontend_label'                => 'label',
            'source_model'                  => 'source',
            'is_required'                   => 'required',
            'is_user_defined'               => 'user_defined',
            'default_value'                 => 'default',
            'is_unique'                     => 'unique',
            'is_global'                     => 'global',
        );
    }
}
