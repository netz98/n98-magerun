<?php

namespace N98\Magento\Command\Eav\Attribute;

use InvalidArgumentException;
use Mage;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RemoveCommandTest
 * @package N98\Magento\Command\Eav\Attribute
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoveCommandTest extends TestCase
{
    public function testCommandThrowsExceptionIfInvalidEntityType()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $this->expectException(InvalidArgumentException::class);

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'not_a_valid_type', 'attributeCode' => ['someAttribute']]
        );
    }

    public function testCommandPrintsErrorIfAttributeNotExists()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => ['not_an_attribute']]
        );

        self::assertStringContainsString(
            'Attribute: "not_an_attribute" does not exist for entity type: "catalog_product"',
            $commandTester->getDisplay()
        );
    }

    public function testAttributeIsSuccessfullyRemoved()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $entityType = 'catalog_product';
        $attributeCode = 'crazyCoolAttribute';
        $this->createAttribute($entityType, $attributeCode, ['type'  => 'text', 'input' => 'text', 'label' => 'Test Attribute']);

        self::assertTrue($this->attributeExists($entityType, $attributeCode));
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => $entityType, 'attributeCode' => [$attributeCode]]
        );

        self::assertFalse($this->attributeExists($entityType, $attributeCode));
    }

    /**
     * @param string $entityTypeCode
     * @dataProvider entityTypeProvider
     */
    public function testOrderAttributeIsSuccessfullyRemoved($entityTypeCode)
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $attributeCode = 'crazyCoolAttribute';
        $this->createAttribute($entityTypeCode, $attributeCode, ['type'  => 'text', 'input' => 'text', 'label' => 'Test Attribute']);

        self::assertTrue($this->attributeExists($entityTypeCode, $attributeCode));
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => $entityTypeCode, 'attributeCode' => [$attributeCode]]
        );

        self::assertFalse($this->attributeExists($entityTypeCode, $attributeCode));
    }

    public function testCanRemoveMultipleAttributes()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $attributeCode1 = 'crazyCoolAttribute1';
        $attributeCode2 = 'crazyCoolAttribute2';
        $this->createAttribute('catalog_product', $attributeCode1, ['type'  => 'text', 'input' => 'text', 'label' => 'Test Attribute 1']);

        $this->createAttribute('catalog_product', $attributeCode2, ['type'  => 'text', 'input' => 'text', 'label' => 'Test Attribute 2']);

        self::assertTrue($this->attributeExists('catalog_product', $attributeCode1));
        self::assertTrue($this->attributeExists('catalog_product', $attributeCode2));
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => [$attributeCode1, $attributeCode2]]
        );

        self::assertFalse($this->attributeExists('catalog_product', $attributeCode1));
        self::assertFalse($this->attributeExists('catalog_product', $attributeCode2));

        self::assertStringContainsString(
            'Successfully removed attribute: "crazyCoolAttribute1" from entity type: "catalog_product"',
            $commandTester->getDisplay()
        );

        self::assertStringContainsString(
            'Successfully removed attribute: "crazyCoolAttribute2" from entity type: "catalog_product"',
            $commandTester->getDisplay()
        );
    }

    public function testCanRemoveMultipleAttributesIfSomeNotExist()
    {
        $application = $this->getApplication();
        $application->add(new RemoveCommand());
        $application->setAutoExit(false);
        $command = $this->getApplication()->find('eav:attribute:remove');

        $attributeCode1 = 'crazyCoolAttribute1';
        $attributeCode2 = 'crazyCoolAttribute2';
        $this->createAttribute('catalog_product', $attributeCode1, ['type'  => 'text', 'input' => 'text', 'label' => 'Test Attribute 1']);

        self::assertTrue($this->attributeExists('catalog_product', $attributeCode1));
        self::assertFalse($this->attributeExists('catalog_product', $attributeCode2));
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'       => $command->getName(), 'entityType'    => 'catalog_product', 'attributeCode' => [$attributeCode1, $attributeCode2]]
        );

        self::assertFalse($this->attributeExists('catalog_product', $attributeCode1));
        self::assertFalse($this->attributeExists('catalog_product', $attributeCode2));

        self::assertStringContainsString(
            'Attribute: "crazyCoolAttribute2" does not exist for entity type: "catalog_product"',
            $commandTester->getDisplay()
        );

        self::assertStringContainsString(
            'Successfully removed attribute: "crazyCoolAttribute1" from entity type: "catalog_product"',
            $commandTester->getDisplay()
        );
    }

    /**
     * @return array
     */
    public static function entityTypeProvider()
    {
        return [['catalog_category'], ['catalog_product'], ['creditmemo'], ['customer'], ['customer_address'], ['invoice'], ['order'], ['shipment']];
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     * @param array $data
     */
    protected function createAttribute($entityType, $attributeCode, $data)
    {
        $setup = Mage::getModel('eav/entity_setup', 'core_setup');
        $setup->addAttribute($entityType, $attributeCode, $data);
    }

    /**
     * @param string $entityType
     * @param string $attributeCode
     * @return bool
     */
    protected function attributeExists($entityType, $attributeCode)
    {
        $codes = Mage::getModel('eav/config')->getEntityAttributeCodes($entityType);
        return in_array($attributeCode, $codes);
    }
}
