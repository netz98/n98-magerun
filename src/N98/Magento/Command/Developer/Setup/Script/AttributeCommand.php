<?php

namespace N98\Magento\Command\Developer\Setup\Script;

use Exception;
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

    /**
     * @param InputInterface $input
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

        try {
            $entityType = $input->getArgument('entityType');
            $attributeCode = $input->getArgument('attributeCode');

            $attribute = $this->getAttribute($entityType, $attributeCode);

            $generator = Factory::create($entityType, $attribute);
            $generator->setReadConnection(
                $this->_getModel('core/resource', 'Mage_Core_Model_Resource')->getConnection('core_read')
            );
            $code = $generator->generateCode();
            $warnings = $generator->getWarnings();

            $output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
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
        $attribute = $this->_getModel('catalog/resource_eav_attribute', 'Mage_Catalog_Model_Resource_Eav_Attribute')
            ->loadByCode($entityType, $attributeCode);

        return $attribute;
    }
}
