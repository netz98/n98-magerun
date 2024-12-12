<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Config;
use Mage_Eav_Model_Entity_Setup;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
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
    protected function configure(): void
    {
        $this
            ->setName('eav:attribute:remove')
            ->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
            ->addArgument('attributeCode', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Attribute Code')
            ->setDescription('Removes attribute for a given attribute code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $entityType = $input->getArgument('entityType');

        try {
            /** @var Mage_Eav_Model_Config $model */
            $model = Mage::getModel('eav/config');
            $attributes = $model->getEntityAttributeCodes($entityType);
        } catch (Mage_Core_Exception $mageCoreException) {
            throw new InvalidArgumentException($mageCoreException->getMessage(), $mageCoreException->getCode(), $mageCoreException);
        }

        $mageEavModelEntitySetup = new Mage_Eav_Model_Entity_Setup('core_setup');
        foreach ($input->getArgument('attributeCode') as $attributeCode) {
            if (!in_array($attributeCode, $attributes)) {
                $message = sprintf(
                    'Attribute: "%s" does not exist for entity type: "%s"',
                    $attributeCode,
                    $entityType,
                );
                $output->writeln(sprintf('<comment>%s</comment>', $message));
            } else {
                $mageEavModelEntitySetup->removeAttribute($entityType, $attributeCode);

                // required with EAV attribute caching added in OpenMage 20.1.0
                // @phpstan-ignore function.alreadyNarrowedType
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
                        $entityType,
                    ),
                );
            }
        }

        return Command::SUCCESS;
    }
}
