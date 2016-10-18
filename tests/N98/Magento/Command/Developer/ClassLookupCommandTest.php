<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ClassLookupCommandTest extends TestCase
{
    /**
     * Test that the class lookup command resolves to the expected Magento class, and optionally
     * whether it outputs a notice informing that the class doesn't exist
     * @dataProvider classLookupProvider
     *
     * @param string $type     Model, helper, block
     * @param string $name     Magento dev code
     * @param string $expected Resolved class name
     * @param bool   $exists   Whether the resolved class should exist
     */
    public function testExecute($type, $name, $expected, $exists)
    {
        $application = $this->getApplication();
        $application->add(new ClassLookupCommand());
        $command = $this->getApplication()->find('dev:class:lookup');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'type'    => $type,
                'name'    => $name,
            )
        );

        $output = $commandTester->getDisplay();
        $this->assertRegExp(sprintf('/%s/', $expected), $output);

        $existsAssertion = (!$exists) ? 'assertRegExp' : 'assertNotRegExp';
        $this->{$existsAssertion}(sprintf('/%s/', 'does not exist'), $output);
    }

    /**
     * Provide data for the class lookup testExecute()
     * @return array
     */
    public function classLookupProvider()
    {
        return array(
            array(
                'type'     => 'model',
                'name'     => 'catalog/product',
                'expected' => 'Mage_Catalog_Model_Product',
                'exists'   => true,
            ),
            array(
                'type'     => 'model',
                'name'     => 'catalog/nothing_to_see_here',
                'expected' => 'Mage_Catalog_Model_Nothing_To_See_Here',
                'exists'   => false,
            ),
            array(
                'type'     => 'helper',
                'name'     => 'checkout/cart',
                'expected' => 'Mage_Checkout_Helper_Cart',
                'exists'   => true,
            ),
            array(
                'type'     => 'helper',
                'name'     => 'checkout/stolen_creditcards',
                'expected' => 'Mage_Checkout_Helper_Stolen_Creditcards',
                'exists'   => false,
            ),
            array(
                'type'     => 'block',
                'name'     => 'customer/account_dashboard',
                'expected' => 'Mage_Customer_Block_Account_Dashboard',
                'exists'   => true,
            ),
            array(
                'type'     => 'block',
                'name'     => 'customer/my_code_snippets',
                'expected' => 'Mage_Customer_Block_My_Code_Snippets',
                'exists'   => false,
            ),
        );
    }
}
