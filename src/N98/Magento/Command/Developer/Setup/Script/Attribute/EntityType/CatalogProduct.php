<?php

namespace N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType;

class CatalogProduct extends AbstractEntityType implements EntityType
{
    /**
     * Gets key legend for catalog product attribute
     *
     * @return array
     */
    protected function _getKeyMapping()
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

    /**
     * @return string
     */
    public function generateCode()
    {
        // get a map of "real attribute properties to properties used in setup resource array
        $realToSetupKeyLegend = $this->_getKeyMapping();

        // swap keys from above
        $data = $this->attribute->getData();
        $keysLegend = array_keys($realToSetupKeyLegend);
        $newData = array();

        foreach ($data as $key => $value) {
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
            $this->warnings[] = <<<TEXT
<warning>WARNING, value detected in attribute_model. We've never seen a value there before and this script doesn't handle it.  Caution, etc. </warning>
TEXT;
        }

        if ($newData['is_used_for_price_rules']) {
            $this->warnings[] = <<<TEXT
<error>WARNING, non false value detected in is_used_for_price_rules. The setup resource migration scripts may not support this (per 1.7.0.1)</error>
TEXT;
        }

        //load values for attributes (if any exist)
        $newData['option'] = $this->getOptions($this->attribute);

        //get text for script
        $arrayCode = var_export($newData, true);

//generate script using simple string concatenation, making
        //a single tear fall down the cheek of a CS professor
        $script = "<?php
\$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

\$attr = $arrayCode;
\$setup->addAttribute('catalog_product', '" . $this->attribute->getAttributeCode() . "', \$attr);
            ";

        $attributeLabels = $this->getAttributeLabels($this->attribute);
        $attributeLabelsCode = var_export($attributeLabels, true);

        $labelsScript = "
\$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', '" . $this->attribute->getAttributeCode() . "');
\$attribute->setStoreLabels($attributeLabelsCode);
\$attribute->save();
";
        $script .= $labelsScript;

        return $script;
    }
}
