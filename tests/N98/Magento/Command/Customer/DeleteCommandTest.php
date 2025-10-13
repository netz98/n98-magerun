<?php

declare(strict_types=1);

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

final class DeleteCommandTest extends TestCase
{
    private $command;

    private $customerModel;

    private $customerCollection;

    private $questionHelper;

    private $parameterHelper;



    private function getResourceClassName($mage1Code)
    {
        return get_class(Mage::getResourceModel($mage1Code));
    }

    private function getModelClassName($mage1Code)
    {
        return get_class(Mage::getModel($mage1Code));
    }

    /**
     * @param string[] $methods
     */
    private function getCustomerModel(array $methods)
    {
        $className = $this->getModelClassName('customer/customer');
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string[] $methods
     */
    private function getCustomerCollection(array $methods)
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
        $application = $this->getApplication();
        $application->initMagento();

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

        $website = $this->getMockBuilder('Mage_Core_Model_Website')
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
            ->willReturn($website);

        $website
            ->method('getId')
            ->willReturn(1);
    }

    public function testCanDeleteById()
    {
        $this->customerModel
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName(), 'id' => '1', '--force'   => true],
        );

        $this->assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(1))
            ->method('getId')
            ->willReturn(null);

        $this->customerModel
            ->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects($this->once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::at(4))
            ->method('getId')
            ->willReturn(1);

        $this->customerModel
            ->expects($this->once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'mike@testing.com', '--force'   => true],
        );

        $this->assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testCustomerNotFound()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects(self::exactly(2))
            ->method('getId')
            ->willReturn(null);

        $this->customerModel
            ->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturn($this->customerModel);

        $this->customerModel
            ->expects($this->once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->willReturn($this->customerModel);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => 'mike@testing.com', '--force'   => true],
        );

        $this->assertStringContainsString('No customer found!', $commandTester->getDisplay());
    }

    public function testDeleteFailed()
    {
        $this->customerModel
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new Exception('Failed to save'));

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1', '--force'   => true],
        );

        $this->assertStringContainsString('Failed to save', $commandTester->getDisplay());
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
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');
        $command->getHelperSet()->set($this->questionHelper, 'question');
        $command->getHelperSet()->set($this->parameterHelper, 'parameter');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true],
        );

        $this->assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testBatchDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects($this->atLeastOnce())
            ->method('addAttributeToSelect')
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->questionHelper
            ->expects($this->once())
            ->method('ask')
            ->willReturn(false);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), '--all'     => true],
        );

        $this->assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testRangeDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects($this->atLeastOnce())
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
            ->expects($this->once())
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
            ['command'   => $command->getName(), '--range'   => true],
        );

        $this->assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemoveStopsDeletion()
    {
        $this->customerModel
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('loadByEmail');

        $this->questionHelper
            ->expects($this->once())
            ->method('askConfirmation')
            ->willReturn(false);

        $this->customerModel
            ->expects($this->never())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1'],
        );

        $this->assertStringContainsString('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemovePromptAllowsDeletion()
    {
        $this->customerModel
            ->expects($this->once())
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
            ->expects($this->never())
            ->method('loadByEmail');

        $this->questionHelper
            ->expects($this->once())
            ->method('askConfirmation')
            ->willReturn(true);

        $this->customerModel
            ->expects($this->once())
            ->method('delete');

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName(), 'id'        => '1'],
        );

        $this->assertStringContainsString('successfully deleted', $commandTester->getDisplay());
    }

    public function testPromptDeleteAllAndDeleteRangeAndAbort()
    {
        $this->questionHelper
            ->expects(self::exactly(3))
            ->method('askConfirmation')->willReturnOnConsecutiveCalls(true, false, false);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester->execute(
            ['command'   => $command->getName()],
        );

        $this->assertStringContainsString('nothing to do', $commandTester->getDisplay());
    }

    public function testPromptAllCanDeleteAll()
    {
        $this->questionHelper
            ->expects(self::exactly(2))
            ->method('askConfirmation')
            ->willReturn(true);

        $this->customerCollection
            ->expects(self::exactly(3))
            ->method('addAttributeToSelect')
            ->willReturnMap([['firstname', false, $this->customerCollection], ['lastname', false, $this->customerCollection], ['email', false, $this->customerCollection]]);

        $this->command
            ->expects($this->once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->willReturn(3);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');
        $command->getHelperSet()->set($this->questionHelper, 'question');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true],
        );

        $this->assertStringContainsString('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testPromptRangeCanDeleteRange()
    {
        $this->questionHelper
            ->expects(self::exactly(3))
            ->method('askConfirmation')->willReturnOnConsecutiveCalls(true, false, true);

        $this->customerCollection
            ->expects($this->atLeastOnce())
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
            ->expects($this->once())
            ->method('addAttributeToFilter')->willReturnSelf();

        $this->command
            ->expects($this->once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->willReturn(3);

        $application = $this->getApplication();
        $application->add($this->command);

        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command'   => $command->getName(), '--force'   => true],
        );

        $this->assertStringContainsString('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testBatchDelete()
    {
        $command = $this->getMockBuilder(DeleteCommand::class)
            ->setMethods(['deleteCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $command
            ->expects(self::exactly(2))
            ->method('deleteCustomer')->willReturnOnConsecutiveCalls(true, new Exception('Failed to delete'));

        $reflectionObject = new ReflectionObject($command);
        $reflectionMethod = $reflectionObject->getMethod('batchDelete');
        $reflectionMethod->setAccessible(true);

        $data = new ArrayIterator([$this->customerModel, $this->customerModel]);

        $collection = $this->getCustomerCollection(['getIterator']);

        $collection
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn($data);

        $result = $reflectionMethod->invokeArgs($command, [$collection]);

        $this->assertSame(1, $result);
    }

    public function testValidateInt()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The range should be numeric and above 0 e.g. 1');
        $reflectionObject = new ReflectionObject($this->command);
        $reflectionMethod = $reflectionObject->getMethod('validateInt');
        $reflectionMethod->setAccessible(true);

        $resultValid = $reflectionMethod->invokeArgs($this->command, ['5']);
        $this->assertSame(5, $resultValid);
        $reflectionMethod->invokeArgs($this->command, ['bad input']); // Exception!
    }
}
