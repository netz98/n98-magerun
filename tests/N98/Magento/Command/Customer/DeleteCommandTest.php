<?php

namespace N98\Magento\Command\Customer;

use Mage;
use Exception;
use ReflectionObject;
use ArrayIterator;
use RuntimeException;
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
            return get_class(Mage::getResourceModel($mage2Code));
        } else {
            return get_class(Mage::getResourceModel($mage1Code));
        }
    }

    protected function getModelClassName($mage1Code, $mage2Code)
    {
        // Get correct model classes to mock
        if ($this->application->getMagentoMajorVersion() == 2) {
            return get_class(Mage::getModel($mage2Code));
        } else {
            return get_class(Mage::getModel($mage1Code));
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

    public function setUp(): void
    {
        $this->application = $this->getApplication();
        $this->application->initMagento();

        $this->customerModel = $this->getCustomerModel(['loadByEmail', 'load', 'getId', 'delete', 'setWebsiteId']);
        $this->customerCollection = $this->getCustomerCollection(['addAttributeToSelect', 'addAttributeToFilter']);

        $this->command = $this->getMockBuilder(DeleteCommand::class)
            ->setMethods(['getCustomerModel', 'getCustomerCollection', 'ask', 'askConfirmation', 'getHelper', 'batchDelete'])
            ->getMock();

        $this->dialog = $this->getMockBuilder('Symfony\Component\Console\Helper\DialogHelper')
            ->setMethods(['ask', 'askConfirmation', 'askAndValidate', 'askWebsite', 'getQuestion'])
            ->getMock();

        $this->parameter = $this->getMockBuilder('Symfony\Component\Console\Helper\ParameterHelper')
            ->setMethods(['askWebsite'])
            ->getMock();

        $this->website = $this->getMockBuilder('Mage_Core_Model_Website')
            ->setMethods(['getId'])
            ->getMock();

        $this->command
            ->method('getCustomerModel')
            ->willReturn($this->customerModel);

        $this->command
            ->method('getCustomerCollection')
            ->willReturn($this->customerCollection);

        $this->command
            ->method('getHelper')
            ->willReturnMap([['dialog', $this->dialog], ['parameter', $this->parameter]]);

        $this->dialog
            ->method('getQuestion')
            ->will(self::returnArgument(0));

        $this->parameter
            ->method('askWebsite')
            ->willReturn($this->website);

        $this->website
            ->method('getId')
            ->willReturn(1);
    }

    public function testCanDeleteById()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(1);

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
            ['command'   => $command->getName(), 'id'        => '1', '--force'   => true]
        );

        self::assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->customerModel
            ->expects(self::once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(4))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'mike@testing.com', '--force'   => true]
        );

        self::assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testCustomerNotFound()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::exactly(2))
            ->method('getId')
            ->willReturn(null);

        $this->customerModel
            ->expects(self::once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'mike@testing.com', '--force'   => true]
        );

        self::assertStringContainsString('No customer found!', $commandTester->getDisplay());
    }

    public function testDeleteFailed()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects(self::once())
            ->method('delete')
            ->will(self::throwException(new Exception('Failed to save')));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1', '--force'   => true]
        );

        self::assertStringContainsString('Failed to save', $commandTester->getDisplay());
    }

    public function testPromptForCustomerIdAndDelete()
    {
        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $this->dialog
            ->expects(self::once())
            ->method('ask')
            ->willReturn('1');

        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(1);

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
            ['command'   => $command->getName(), '--force'   => true]
        );

        self::assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testBatchDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--all'     => true]
        );

        self::assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testRangeDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->dialog
            ->expects(self::exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects(self::at(0))
            ->method('askAndValidate')
            ->willReturn('1');

        $this->dialog
            ->expects(self::at(1))
            ->method('askAndValidate')
            ->willReturn('10');

        $this->customerCollection
            ->expects(self::once())
            ->method('addAttributeToFilter')
            ->willReturn($this->customerCollection);

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--range'   => true]
        );

        self::assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemoveStopsDeletion()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(false);

        $this->customerModel
            ->expects(self::never())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1']
        );

        self::assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemovePromptAllowsDeletion()
    {
        $this->customerModel
            ->expects(self::once())
            ->method('load')
            ->with('1')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::at(2))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects(self::never())
            ->method('loadByEmail');

        $this->dialog
            ->expects(self::once())
            ->method('askConfirmation')
            ->willReturn(true);

        $this->customerModel
            ->expects(self::once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1']
        );

        self::assertStringContainsString('successfully deleted', $commandTester->getDisplay());
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
            ['command'   => $command->getName()]
        );

        self::assertStringContainsString('nothing to do', $commandTester->getDisplay());
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
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->command
            ->expects(self::once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->willReturn(3);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true]
        );

        self::assertStringContainsString('Successfully deleted 3 customer/s', $commandTester->getDisplay());
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
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->dialog
            ->expects(self::exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects(self::at(0))
            ->method('askAndValidate')
            ->willReturn('1');

        $this->dialog
            ->expects(self::at(1))
            ->method('askAndValidate')
            ->willReturn('10');

        $this->customerCollection
            ->expects(self::once())
            ->method('addAttributeToFilter')
            ->will(self::returnSelf());

        $this->command
            ->expects(self::once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->willReturn(3);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true]
        );

        self::assertStringContainsString('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testBatchDelete()
    {
        $command = $this->getMockBuilder(DeleteCommand::class)
            ->setMethods(['deleteCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $command
            ->expects(self::exactly(2))
            ->method('deleteCustomer')
            ->will(self::onConsecutiveCalls(true, new Exception('Failed to delete')));

        $refObject = new ReflectionObject($command);
        $method = $refObject->getMethod('batchDelete');
        $method->setAccessible(true);

        $data = new ArrayIterator([$this->customerModel, $this->customerModel]);

        $collection = $this->getCustomerCollection(['getIterator']);

        $collection
            ->expects(self::once())
            ->method('getIterator')
            ->willReturn($data);

        $result = $method->invokeArgs($command, [$collection]);

        self::assertEquals(1, $result);
    }

    public function testValidateInt()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The range should be numeric and above 0 e.g. 1');
        $refObject = new ReflectionObject($this->command);
        $method = $refObject->getMethod('validateInt');
        $method->setAccessible(true);

        $resultValid = $method->invokeArgs($this->command, ['5']);
        self::assertEquals(5, $resultValid);
        $method->invokeArgs($this->command, ['bad input']); // Exception!
    }
}
