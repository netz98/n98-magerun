<?php

namespace N98\Magento\Command\Eav\Attribute;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class RemoveCommandTest extends TestCase
{
    public function testCommandThrowsExceptionIfInvalidEntityTypeFormat()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $this->setExpectedException('InvalidArgumentException', 'Entity type: "notavalidtype" is invalid');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'entityType'    => 'notavalidtype',
                'attributeCode' => 'someAttribute',
            )
        );
    }

    public function testCommandThrowsExceptionIfInvalidEntityType()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $this->setExpectedException('Mage_Core_Exception', 'Invalid entity_type specified: not_a_valid_type');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                 'command'       => $command->getName(),
                 'entityType'    => 'not_a_valid_type',
                 'attributeCode' => 'someAttribute',
            )
        );
    }

    public function testCommandThrowsExceptionIfAttributeNotExist()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $this->setExpectedException(
            'InvalidArgumentException',
            'Attribute: "not_an_attribute" does not exist for entity type: "catalog_product"'
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'entityType'    => 'catalog_product',
                'attributeCode' => 'not_an_attribute',
            )
        );
    }

    public function testAttributeIsSuccessfullyRemoved()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $entityType     = 'catalog_product';
        $attributeCode  = 'crazyCoolAttribute';
        $this->createAttribute($entityType, $attributeCode, array(
            'type'  =>'text',
            'input' =>'text',
            'label' =>'Test Attribute',
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'entityType'    => $entityType,
                'attributeCode' => $attributeCode,
            )
        );

        $attribute = \Mage::getModel('eav/config')->getAttribute($entityType, $attributeCode);
        $this->assertNull($attribute->getId());
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     * @param array $data
     */
    protected function createAttribute($entityType, $attributeCode, $data)
    {
        $setup = \Mage::getModel('eav/entity_setup','core_setup');
        $setup->addAttribute($entityType, $attributeCode, $data);
    }
}
