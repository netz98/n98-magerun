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
            ['command' => $command->getName(), 'type'    => $type, 'name'    => $name]
        );

        $output = $commandTester->getDisplay();
        self::assertMatchesRegularExpression(sprintf('/%s/', $expected), $output);

        $existsAssertion = (!$exists) ? 'assertMatchesRegularExpression' : 'assertDoesNotMatchRegularExpression';
        $this->{$existsAssertion}(sprintf('/%s/', 'does not exist'), $output);
    }

    /**
     * Provide data for the class lookup testExecute()
     * @return array
     */
    public function classLookupProvider()
    {
        return [['type'     => 'model', 'name'     => 'catalog/product', 'expected' => 'Mage_Catalog_Model_Product', 'exists'   => true], ['type'     => 'model', 'name'     => 'catalog/nothing_to_see_here', 'expected' => 'Mage_Catalog_Model_Nothing_To_See_Here', 'exists'   => false], ['type'     => 'helper', 'name'     => 'checkout/cart', 'expected' => 'Mage_Checkout_Helper_Cart', 'exists'   => true], ['type'     => 'helper', 'name'     => 'checkout/stolen_creditcards', 'expected' => 'Mage_Checkout_Helper_Stolen_Creditcards', 'exists'   => false], ['type'     => 'block', 'name'     => 'customer/account_dashboard', 'expected' => 'Mage_Customer_Block_Account_Dashboard', 'exists'   => true], ['type'     => 'block', 'name'     => 'customer/my_code_snippets', 'expected' => 'Mage_Customer_Block_My_Code_Snippets', 'exists'   => false]];
    }
}
