<?php

namespace N98\Magento\Command\Customer;

use Mage;
use Exception;
use N98\Util\Console\Helper\ParameterHelper;
use ReflectionObject;
use ArrayIterator;
use RuntimeException;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteCommandTest extends TestCase
{
    protected $command;
    protected $customerModel;
    protected $customerCollection;
    protected $questionHelper;
    protected $parameterHelper;
    protected $website;
    protected $application;

    protected function getResourceClassName($mage1Code)
    {
        return get_class(Mage::getResourceModel($mage1Code));
    }

    protected function getModelClassName($mage1Code)
    {
        return get_class(Mage::getModel($mage1Code));
    }

    protected function getCustomerModel(array $methods)
    {
        $className = $this->getModelClassName('customer/customer');
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getCustomerCollection(array $methods)
    {
        $className = $this->getResourceClassName('customer/customer_collection');
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function setUp(): void
    {
        $this->markTestIncomplete('This tests are not compatible with PHPUnit 9. Refactring is needed.');
        $this->application = $this->getApplication();
        $this->application->initMagento();

        $this->customerModel = $this->getCustomerModel(['loadByEmail', 'load', 'getId', 'delete', 'setWebsiteId']);
        $this->customerCollection = $this->getCustomerCollection(['addAttributeToSelect', 'addAttributeToFilter']);

        $this->command = $this->getMockBuilder(DeleteCommand::class)
            ->setMethods(['getCustomerModel', 'getCustomerCollection', 'ask', 'askConfirmation', 'getHelper', 'batchDelete'])
            ->getMock();

        $this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
            ->onlyMethods(['ask'])
            ->getMock();

        $this->parameterHelper = $this->getMockBuilder(ParameterHelper::class)
            ->onlyMethods(['askWebsite'])
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
            ->willReturnMap([['dialog', $this->questionHelper], ['parameter', $this->parameterHelper]]);

        $this->parameterHelper
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
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'id' => '1', '--force'   => true]
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
        $command->getHelperSet()->set($this->questionHelper, 'question');

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
        $command->getHelperSet()->set($this->questionHelper, 'question');

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
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1', '--force'   => true]
        );

        self::assertStringContainsString('Failed to save', $commandTester->getDisplay());
    }

    public function testPromptForCustomerIdAndDelete()
    {
        $this->questionHelper
            ->expects(self::at(0))
            ->method('ask')
            ->willReturn(false);

        $this->questionHelper
            ->expects(self::at(1))
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
        $command->getHelperSet()->set($this->questionHelper, 'question');
        $command->getHelperSet()->set($this->parameterHelper, 'parameter');

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

        $this->questionHelper
            ->expects(self::once())
            ->method('ask')
            ->willReturn(false);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

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

        $this->questionHelper
            ->expects(self::exactly(2))
            ->method('ask');

        $this->questionHelper
            ->expects(self::at(0))
            ->method('ask')
            ->willReturn('1');

        $this->questionHelper
            ->expects(self::at(1))
            ->method('ask')
            ->willReturn('10');

        $this->customerCollection
            ->expects(self::once())
            ->method('addAttributeToFilter')
            ->willReturn($this->customerCollection);

        $this->questionHelper
            ->expects(self::at(2))
            ->method('ask')
            ->willReturn(false);

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

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

        $this->questionHelper
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
        $command->getHelperSet()->set($this->questionHelper, 'question');

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

        $this->questionHelper
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
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1']
        );

        self::assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testPromptDeleteAllAndDeleteRangeAndAbort()
    {
        $this->questionHelper
            ->expects(self::exactly(3))
            ->method('askConfirmation')
            ->will(self::onConsecutiveCalls(true, false, false));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName()]
        );

        self::assertStringContainsString('nothing to do', $commandTester->getDisplay());
    }

    public function testPromptAllCanDeleteAll()
    {
        $this->questionHelper
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
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true]
        );

        self::assertStringContainsString('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testPromptRangeCanDeleteRange()
    {
        $this->questionHelper
            ->expects(self::exactly(3))
            ->method('askConfirmation')
            ->will(self::onConsecutiveCalls(true, false, true));

        $this->customerCollection
            ->expects(self::atLeastOnce())
            ->method('addAttributeToSelect')
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->questionHelper
            ->expects(self::exactly(2))
            ->method('askAndValidate');

        $this->questionHelper
            ->expects(self::at(0))
            ->method('askAndValidate')
            ->willReturn('1');

        $this->questionHelper
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
