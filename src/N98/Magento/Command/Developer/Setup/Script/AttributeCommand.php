<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Setup\Script;

use Exception;
use Mage;
use Mage_Core_Exception;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType\EntityType;
use N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Ported attribute migration script from:
 *
 * https://github.com/astorm/Pulsestorm/blob/master/magento-create-setup.php
 * https://github.com/astorm/Pulsestorm/blob/2863201b19367d02483e01b1c03b54b979d87278/_trash/magento-create-setup.php
 *
 * It creates attribute script for existing attribute
 *
 * Originally created by Alan Storm
 *
 * @package N98\Magento\Command\Developer\Setup\Script
 * @author Dusan Lukic <ldusan84@gmail.com>
 */
class AttributeCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_ENTITY_TYPE = 'entityType';

    public const COMMAND_ARGUMENT_ATTRIBUTE_CODE = 'attributeCode';

    /**
     * @var string
     */
    protected static $defaultName = 'dev:setup:script:attribute';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Creates attribute script for a given attribute code.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ENTITY_TYPE,
                InputArgument::REQUIRED,
                'Entity Type Code like catalog_product'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ATTRIBUTE_CODE,
                InputArgument::REQUIRED,
                'Attribute Code'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var string $entityType */
            $entityType = $input->getArgument(self::COMMAND_ARGUMENT_ENTITY_TYPE);
            /** @var string $attributeCode */
            $attributeCode = $input->getArgument(self::COMMAND_ARGUMENT_ATTRIBUTE_CODE);
            /** @var string $attribute */
            $attribute = $this->getAttribute($entityType, $attributeCode);

            /** @var EntityType $generator */
            $generator = Factory::create($entityType, $attribute);
            $generator->setReadConnection(
                Mage::getModel('core/resource')->getConnection('core_read')
            );
            $code = $generator->generateCode();
            $warnings = $generator->getWarnings();

            $output->writeln(implode(PHP_EOL, $warnings) . PHP_EOL . $code);
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function getAttribute(string $entityType, string $attributeCode)
    {
        return Mage::getModel('catalog/resource_eav_attribute')
            ->loadByCode($entityType, $attributeCode);
    }
}
