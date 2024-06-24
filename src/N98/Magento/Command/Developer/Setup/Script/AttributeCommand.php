<?php
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
 * @author Dusan Lukic <ldusan84@gmail.com>
 */

namespace N98\Magento\Command\Developer\Setup\Script;

use Exception;
use N98\Magento\Command\AbstractCommand;
use N98\Magento\Command\Developer\Setup\Script\Attribute\EntityType\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AttributeCommand extends AbstractCommand
{
    protected function configure(): void
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
        $this->initMagento();

        try {
            $entityType = $input->getArgument('entityType');
            $attributeCode = $input->getArgument('attributeCode');

            $attribute = $this->getAttribute($entityType, $attributeCode);

            $generator = Factory::create($entityType, $attribute);
            $generator->setReadConnection(
                $this->_getModel('core/resource')->getConnection('core_read')
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
     * @return mixed
     */
    protected function getAttribute(string $entityType, string $attributeCode)
    {
        return $this
            ->_getModel('catalog/resource_eav_attribute')
            ->loadByCode($entityType, $attributeCode);
    }
}
