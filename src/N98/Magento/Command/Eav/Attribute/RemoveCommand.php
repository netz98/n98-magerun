<?php

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Entity_Setup;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remove EAV attribute command
 *
 * @package N98\Magento\Command\Eav\Attribute
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('eav:attribute:remove')
            ->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
            ->addArgument('attributeCode', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Attribute Code')
            ->setDescription('Removes attribute for a given attribute code');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $entityType = $input->getArgument('entityType');

        try {
            $attributes = Mage::getModel('eav/config')->getEntityAttributeCodes($entityType);
        } catch (Mage_Core_Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        foreach ($input->getArgument('attributeCode') as $attributeCode) {
            if (!in_array($attributeCode, $attributes)) {
                $message = sprintf(
                    'Attribute: "%s" does not exist for entity type: "%s"',
                    $attributeCode,
                    $entityType
                );
                $output->writeln(sprintf('<comment>%s</comment>', $message));
            } else {
                $setup->removeAttribute($entityType, $attributeCode);

                // required with EAV attribute caching added in OpenMage 20.1.0
                if (method_exists('Mage', 'getOpenMageVersion')
                    && version_compare(Mage::getOpenMageVersion(), '20.1', '>=')
                ) {
                    Mage::app()->getCacheInstance()->cleanType('eav');
                    Mage::dispatchEvent('adminhtml_cache_refresh_type', ['type' => 'eav']);
                }

                $output->writeln(
                    sprintf(
                        '<info>Successfully removed attribute: "%s" from entity type: "%s"</info>',
                        $attributeCode,
                        $entityType
                    )
                );
            }
        }
        return 0;
    }
}
