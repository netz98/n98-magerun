<?php

namespace N98\Magento\Command\Customer;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteCommandTest extends TestCase
{
    protected $command;
    protected $customerModel;
    protected $customerCollection;
    protected $dialog;
    protected $parameter;
    protected $website;
    protected $application;

    protected function getResourceClassName($mage1Code, $mage2Code)
    {
        // Get correct model classes to mock
        if ($this->application->getMagentoMajorVersion() == 2) {
            return get_class(\Mage::getResourceModel($mage2Code));
        } else {
            return get_class(\Mage::getResourceModel($mage1Code));
        }
    }

    protected function getModelClassName($mage1Code, $mage2Code)
    {
        // Get correct model classes to mock
        if ($this->application->getMagentoMajorVersion() == 2) {
            return get_class(\Mage::getModel($mage2Code));
        } else {
            return get_class(\Mage::getModel($mage1Code));
        }
    }

    protected function getCustomerModel(array $methods)
    {
        $className = $this->getModelClassName('customer/customer', 'Mage_Customer_Model_Customer');
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getCustomerCollection(array $methods)
    {
        $className = $this->getResourceClassName(
            'customer/customer_collection',
            'Mage_Customer_Model_Resource_Customer_Collection'
        );

        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUp()
    {
        $this->application = $this->getApplication();
        $this->application->initMagento();

        $this->customerModel = $this->getCustomerModel(array('loadByEmail', 'load', 'getId', 'delete', 'setWebsiteId'));
        $this->customerCollection = $this->getCustomerCollection(array('addAttributeToSelect', 'addAttributeToFilter'));

        $this->command = $this->getMockBuilder('\N98\Magento\Command\Customer\DeleteCommand')
            ->setMethods(array(
                'getCustomerModel',
                'getCustomerCollection',
                'ask',
                'askConfirmation',
                'getHelper',
                'batchDelete',
            ))
            ->getMock();

        $this->dialog = $this->getMockBuilder('Symfony\Component\Console\Helper\DialogHelper')
            ->setMethods(array('ask', 'askConfirmation', 'askAndValidate', 'askWebsite', 'getQuestion'))
            ->getMock();

        $this->parameter = $this->getMockBuilder('Symfony\Component\Console\Helper\ParameterHelper')
            ->setMethods(array('askWebsite'))
            ->getMock();

        $this->website = $this->getMockBuilder('Mage_Core_Model_Website')
            ->setMethods(array('getId'))
            ->getMock();

        $this->command
            ->expects(self::any())
            ->method('getCustomerModel')
            ->will(self::returnValue($this->customerModel));

        $this->command
            ->expects(self::any())
            ->method('getCustomerCollection')
            ->will(self::returnValue($this->customerCollection));

        $this->command
            ->expects(self::any())
            ->method('getHelper')
            ->will(self::returnValueMap(array(
                array('dialog', $this->dialog),
                array('parameter', $this->parameter),
            )));

        $this->dialog
            ->expects(self::any())
            ->method('getQuestion')
            ->will(self::returnArgument(0));

        $this->parameter
            ->expects(self::any())
            ->method('askWebsite')
            ->will(self::returnValue($this->website));

        $this->website
            ->expects(self::any())
            ->method('getId')
            ->will(self::returnValue(1));
    }

    public function testCanDeleteById()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => '1',
                '--force'   => true,
            )
        );

        self::assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('mike@testing.com')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(null));

        $this->customerModel
            ->expects(self::once())
            ->method('setWebsiteId')
            ->with(1)
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(4))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => 'mike@testing.com',
                '--force'   => true,
            )
        );

        self::assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testCustomerNotFound()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('mike@testing.com')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::exactly(2))
            ->method('getId')
            ->will(self::returnValue(null));

        $this->customerModel
            ->expects(self::once())
            ->method('setWebsiteId')
            ->with(1)
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->will(self::returnValue($this->customerModel));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => 'mike@testing.com',
                '--force'   => true,
            )
        );

        self::assertContains('No customer found!', $commandTester->getDisplay());
    }

    public function testDeleteFailed()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects(self::once())
            ->method('delete')
            ->will(self::throwException(new \Exception('Failed to save')));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => '1',
                '--force'   => true,
            )
        );

        self::assertContains('Failed to save', $commandTester->getDisplay());
    }

    public function testPromptForCustomerIdAndDelete()
    {
        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->will(self::returnValue(false));

        $this->dialog
            ->expects(self::once())
            ->method('ask')
            ->will(self::returnValue('1'));

        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--force'   => true,
            )
        );

        self::assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testBatchDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->will(self::returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->will(self::returnValue(false));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--all'     => true,
            )
        );

        self::assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testRangeDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->will(self::returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects(self::exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects(self::at(0))
            ->method('askAndValidate')
            ->will(self::returnValue('1'));

        $this->dialog
            ->expects(self::at(1))
            ->method('askAndValidate')
            ->will(self::returnValue('10'));

        $this->customerCollection
            ->expects(self::once())
            ->method('addAttributeToFilter')
            ->will(self::returnValue($this->customerCollection));

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->will(self::returnValue(false));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--range'   => true,
            )
        );

        self::assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemoveStopsDeletion()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->will(self::returnValue(false));

        $this->customerModel
            ->expects(self::never())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => '1',
            )
        );

        self::assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemovePromptAllowsDeletion()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->will(self::returnValue($this->customerModel));

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->will(self::returnValue(1));

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->will(self::returnValue(true));

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                'id'        => '1',
            )
        );

        self::assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testPromptDeleteAllAndDeleteRangeAndAbort()
    {
        $this->dialog
            ->expects(self::exactly(3))
            ->method('askConfirmation')
            ->will(self::onConsecutiveCalls(true, false, false));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
            )
        );

        self::assertContains('nothing to do', $commandTester->getDisplay());
    }

    public function testPromptAllCanDeleteAll()
    {
        $this->dialog
            ->expects(self::exactly(2))
            ->method('askConfirmation')
            ->will(self::onConsecutiveCalls(true, true));

        $this->customerCollection
            ->expects(self::exactly(3))
            ->method('addAttributeToSelect')
            ->will(self::returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->command
            ->expects(self::once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->will(self::returnValue(3));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--force'   => true,
            )
        );

        self::assertContains('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testPromptRangeCanDeleteRange()
    {
        $this->dialog
            ->expects(self::exactly(3))
            ->method('askConfirmation')
            ->will(self::onConsecutiveCalls(true, false, true));

        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->will(self::returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects(self::exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects(self::at(0))
            ->method('askAndValidate')
            ->will(self::returnValue('1'));

        $this->dialog
            ->expects(self::at(1))
            ->method('askAndValidate')
            ->will(self::returnValue('10'));

        $this->customerCollection
            ->expects(self::once())
            ->method('addAttributeToFilter')
            ->will(self::returnSelf());

        $this->command
            ->expects(self::once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->will(self::returnValue(3));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--force'   => true,
            )
        );

        self::assertContains('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testBatchDelete()
    {
        $command = $this->getMockBuilder('\N98\Magento\Command\Customer\DeleteCommand')
            ->setMethods(array('deleteCustomer'))
            ->disableOriginalConstructor()
            ->getMock();

        $command
            ->expects(self::exactly(2))
            ->method('deleteCustomer')
            ->will(self::onConsecutiveCalls(true, new \Exception('Failed to delete')));

        $refObject = new \ReflectionObject($command);
        $method = $refObject->getMethod('batchDelete');
        $method->setAccessible(true);

        $data = new \ArrayIterator(array(
            $this->customerModel,
            $this->customerModel,
        ));

        $collection = $this->getCustomerCollection(array('getIterator'));

        $collection
            ->expects(self::once())
            ->method('getIterator')
            ->will(self::returnValue($data));

        $result = $method->invokeArgs($command, array($collection));

        self::assertEquals(1, $result);
    }

    public function testValidateInt()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The range should be numeric and above 0 e.g. 1');
        $refObject = new \ReflectionObject($this->command);
        $method = $refObject->getMethod('validateInt');
        $method->setAccessible(true);

        $resultValid = $method->invokeArgs($this->command, array('5'));
        self::assertEquals(5, $resultValid);
        $method->invokeArgs($this->command, array('bad input')); // Exception!
    }
}
