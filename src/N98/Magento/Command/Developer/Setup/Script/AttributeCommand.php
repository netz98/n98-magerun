<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script;

use Exception;
use Mage_Catalog_Model_Resource_Eav_Attribute;
use Mage_Core_Model_Resource;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates attribute script for existing attribute command
 *
 * Ported attribute migration script from:
 *
 * https://github.com/astorm/Pulsestorm/blob/master/magento-create-setup.php
 * https://github.com/astorm/Pulsestorm/blob/2863201b19367d02483e01b1c03b54b979d87278/_trash/magento-create-setup.php
 *
 * Originally created by Alan Storm
 *
 * @package N98\Magento\Command\Developer\Setup\Script
 *
 * @author Dusan Lukic <ldusan84@gmail.com>
 */
class AttributeCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:setup:script:attribute')
            ->addArgument('entityType', InputArgument::REQUIRED, 'Entity Type Code like catalog_product')
            ->addArgument('attributeCode', InputArgument::REQUIRED, 'Attribute Code')
            ->setDescription('Creates attribute script for a given attribute code');
    }

    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        try {
            $entityType = $input->getArgument('entityType');
            $attributeCode = $input->getArgument('attributeCode');

            $attribute = $this->getAttribute($entityType, $attributeCode);

            /** @var Mage_Core_Model_Resource $coreResource */
            $coreResource = $this->_getModel('core/resource');

            $generator = Factory::create($entityType, $attribute);
            $generator->setReadConnection(
                $coreResource->getConnection('core_read')
            );
            $code = $generator->generateCode();
            $warnings = $generator->getWarnings();

            $output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
        } catch (Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        }

        return 0;
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     *
     * @return mixed
     */
    protected function getAttribute($entityType, $attributeCode)
    {
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $mageCoreModelAbstract */
        $mageCoreModelAbstract = $this->_getModel('catalog/resource_eav_attribute');
        return $mageCoreModelAbstract->loadByCode($entityType, $attributeCode);
    }
}
