<?php

declare(strict_types=1);

namespace N98\Magento\Command\Eav\Attribute;

use Exception;
use InvalidArgumentException;
use Mage;
use Mage_Core_Exception;
use Mage_Eav_Model_Entity_Setup;
use N98\Magento\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * EAV attribute remove command
 *
 * @package N98\Magento\Command\Eav\Attribute
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommand extends AbstractCommand
{
    public const COMMAND_ARGUMENT_ENTITY = 'entityType';

    public const COMMAND_ARGUMENT_ATTRIBUTE = 'attributeCode';

    /**
     * @var string
     */
    protected static $defaultName = 'eav:attribute:remove';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Removes attribute for a given attribute code.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_ENTITY,
                InputArgument::REQUIRED,
                'Entity Type Code like catalog_product'
            )
            ->addArgument(
                self::COMMAND_ARGUMENT_ATTRIBUTE,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Attribute Code'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $entityType */
        $entityType = $input->getArgument(self::COMMAND_ARGUMENT_ENTITY);

        try {
            $attributes = Mage::getModel('eav/config')->getEntityAttributeCodes($entityType);
        } catch (Mage_Core_Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');

        /** @var array<int, string> $attributeCodes */
        $attributeCodes = $input->getArgument(self::COMMAND_ARGUMENT_ATTRIBUTE);
        foreach ($attributeCodes as $attributeCode) {
            if (!in_array($attributeCode, $attributes)) {
                $message = sprintf(
                    'Attribute: "%s" does not exist for entity type: "%s"',
                    $attributeCode,
                    $entityType
                );
                $output->writeln(sprintf('<comment>%s</comment>', $message));
            } else {
                $setup->removeAttribute($entityType, $attributeCode);

                $output->writeln(
                    sprintf(
                        '<info>Successfully removed attribute: "%s" from entity type: "%s"</info>',
                        $attributeCode,
                        $entityType
                    )
                );
            }
        }

        return Command::SUCCESS;
    }
}
