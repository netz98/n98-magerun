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
            ->expects($this->any())
            ->method('getCustomerModel')
            ->will($this->returnValue($this->customerModel));

        $this->command
            ->expects($this->any())
            ->method('getCustomerCollection')
            ->will($this->returnValue($this->customerCollection));

        $this->command
            ->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValueMap(array(
                array('dialog', $this->dialog),
                array('parameter', $this->parameter),
            )));

        $this->dialog
            ->expects($this->any())
            ->method('getQuestion')
            ->will($this->returnArgument(0));

        $this->parameter
            ->expects($this->any())
            ->method('askWebsite')
            ->will($this->returnValue($this->website));

        $this->website
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
    }

    public function testCanDeleteById()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('1')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
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

        $this->assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testCanDeleteByEmail()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('mike@testing.com')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->customerModel
            ->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(4))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->once())
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

        $this->assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testCustomerNotFound()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('mike@testing.com')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(null));

        $this->customerModel
            ->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->once())
            ->method('loadByEmail')
            ->with('mike@testing.com')
            ->will($this->returnValue($this->customerModel));

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

        $this->assertContains('No customer found!', $commandTester->getDisplay());
    }

    public function testDeleteFailed()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('1')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new \Exception('Failed to save')));

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

        $this->assertContains('Failed to save', $commandTester->getDisplay());
    }

    public function testPromptForCustomerIdAndDelete()
    {
        $this->dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(false));

        $this->dialog
            ->expects($this->once())
            ->method('ask')
            ->will($this->returnValue('1'));

        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('1')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->never())
            ->method('loadByEmail');

        $this->customerModel
            ->expects($this->once())
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

        $this->assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testBatchDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects($this->atLeastOnce())
            ->method('addAttributeToSelect')
            ->will($this->returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(false));

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

        $this->assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testRangeDeleteGetsCustomerCollection()
    {
        $this->customerCollection
            ->expects($this->atLeastOnce())
            ->method('addAttributeToSelect')
            ->will($this->returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects($this->exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects($this->at(0))
            ->method('askAndValidate')
            ->will($this->returnValue('1'));

        $this->dialog
            ->expects($this->at(1))
            ->method('askAndValidate')
            ->will($this->returnValue('10'));

        $this->customerCollection
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->will($this->returnValue($this->customerCollection));

        $this->dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(false));

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

        $this->assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemoveStopsDeletion()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('1')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->never())
            ->method('loadByEmail');

        $this->dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(false));

        $this->customerModel
            ->expects($this->never())
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

        $this->assertContains('Aborting delete', $commandTester->getDisplay());
    }

    public function testShouldRemovePromptAllowsDeletion()
    {
        $this->customerModel
            ->expects($this->once())
            ->method('load')
            ->with('1')
            ->will($this->returnValue($this->customerModel));

        $this->customerModel
            ->expects($this->at(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->at(2))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->customerModel
            ->expects($this->never())
            ->method('loadByEmail');

        $this->dialog
            ->expects($this->once())
            ->method('askConfirmation')
            ->will($this->returnValue(true));

        $this->customerModel
            ->expects($this->once())
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

        $this->assertContains('successfully deleted', $commandTester->getDisplay());
    }

    public function testPromptDeleteAllAndDeleteRangeAndAbort()
    {
        $this->dialog
            ->expects($this->exactly(3))
            ->method('askConfirmation')
            ->will($this->onConsecutiveCalls(true, false, false));

        $application = $this->getApplication();
        $application->add($this->command);
        $command = $this->getApplication()->find('customer:delete');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'   => $command->getName(),
            )
        );

        $this->assertContains('nothing to do', $commandTester->getDisplay());
    }

    public function testPromptAllCanDeleteAll()
    {
        $this->dialog
            ->expects($this->exactly(2))
            ->method('askConfirmation')
            ->will($this->onConsecutiveCalls(true, true));

        $this->customerCollection
            ->expects($this->exactly(3))
            ->method('addAttributeToSelect')
            ->will($this->returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->command
            ->expects($this->once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->will($this->returnValue(3));

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

        $this->assertContains('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testPromptRangeCanDeleteRange()
    {
        $this->dialog
            ->expects($this->exactly(3))
            ->method('askConfirmation')
            ->will($this->onConsecutiveCalls(true, false, true));

        $this->customerCollection
            ->expects($this->atLeastOnce())
            ->method('addAttributeToSelect')
            ->will($this->returnValueMap(array(
                array('firstname', false, $this->customerCollection),
                array('lastname', false, $this->customerCollection),
                array('email', false, $this->customerCollection),
            )));

        $this->dialog
            ->expects($this->exactly(2))
            ->method('askAndValidate');

        $this->dialog
            ->expects($this->at(0))
            ->method('askAndValidate')
            ->will($this->returnValue('1'));

        $this->dialog
            ->expects($this->at(1))
            ->method('askAndValidate')
            ->will($this->returnValue('10'));

        $this->customerCollection
            ->expects($this->once())
            ->method('addAttributeToFilter')
            ->will($this->returnSelf());

        $this->command
            ->expects($this->once())
            ->method('batchDelete')
            ->with($this->customerCollection)
            ->will($this->returnValue(3));

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

        $this->assertContains('Successfully deleted 3 customer/s', $commandTester->getDisplay());
    }

    public function testBatchDelete()
    {
        $command = $this->getMockBuilder('\N98\Magento\Command\Customer\DeleteCommand')
            ->setMethods(array('deleteCustomer'))
            ->disableOriginalConstructor()
            ->getMock();

        $command
            ->expects($this->exactly(2))
            ->method('deleteCustomer')
            ->will($this->onConsecutiveCalls(true, new \Exception('Failed to delete')));

        $refObject = new \ReflectionObject($command);
        $method = $refObject->getMethod('batchDelete');
        $method->setAccessible(true);

        $data = new \ArrayIterator(array(
            $this->customerModel,
            $this->customerModel,
        ));

        $collection = $this->getCustomerCollection(array('getIterator'));

        $collection
            ->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue($data));

        $result = $method->invokeArgs($command, array($collection));

        $this->assertEquals(1, $result);
    }

    /**
     * @expectedException   \RuntimeException
     * @expectedExceptionMessage    The range should be numeric and above 0 e.g. 1
     */
    public function testValidateInt()
    {
        $refObject = new \ReflectionObject($this->command);
        $method = $refObject->getMethod('validateInt');
        $method->setAccessible(true);

        $resultValid = $method->invokeArgs($this->command, array('5'));
        $this->assertEquals(5, $resultValid);
        $method->invokeArgs($this->command, array('bad input')); // Exception!
    }
}
