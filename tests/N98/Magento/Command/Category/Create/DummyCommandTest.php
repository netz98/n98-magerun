<?php

namespace N98\Magento\Command\Category\Create;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DummyCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $application->find('category:create:dummy');
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            array(
                'command'                    => $command->getName(),
                'store-id'                   => 1,
                'children-categories-number' => 1,
                'category-name-prefix'       => 'My Awesome Category',
                'category-number'            => 1,
            )
        );

        self::assertRegExp('/CATEGORY: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay());
        self::assertRegExp('/CATEGORY CHILD: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay());

        // Check if the category is created correctly
        $match_parent = "";
        $match_child = "";
        preg_match('/CATEGORY: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay(), $match_parent);
        self::assertTrue($this->checkifCategoryExist($match_parent[2]));
        preg_match('/CATEGORY CHILD: \'My Awesome Category (.+)\' WITH ID: \'(.+)\' CREATED!/', $commandTester->getDisplay(), $match_child);
        self::assertTrue($this->checkifCategoryExist($match_child[2]));

        // Delete category created
        $this->deleteMagentoCategory($match_parent[2]);
        $this->deleteMagentoCategory($match_child[2]);
    }

    protected function checkifCategoryExist($_category_id)
    {
        if (!is_null(\Mage::getModel('catalog/category')->load($_category_id)->getName())) {
            return true;
        }
    }

    protected function deleteMagentoCategory($_category_id)
    {
        \Mage::getModel('catalog/category')->load($_category_id)->delete();
    }

    public function testmanageArguments()
    {
        $application = $this->getApplication();
        $application->add(new DummyCommand());
        $command = $application->find('category:create:dummy');

        $dialog = $this->getMockBuilder(\Symfony\Component\Console\Helper\QuestionHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['ask'])
            ->getMock();

        // ASK - store-id
        $dialog
            ->method('ask')
            ->with(
                self::isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Question\Question')
            )
            ->willReturn(1);

        // ASK - children-categories-number
        $dialog
            ->method('ask')
            ->with(
                self::isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Question\Question')
            )
            ->willReturn(0);

        // ASK - category-name-prefix
        $dialog
            ->method('ask')
            ->with(
                self::isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Question\Question')
            )
            ->willReturn('My Awesome Category ');

        // ASK - category-number
        $dialog
            ->method('ask')
            ->with(
                self::isInstanceOf('Symfony\Component\Console\Input\InputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Output\OutputInterface'),
                self::isInstanceOf('Symfony\Component\Console\Question\Question')
            )
            ->willReturn(0);

        // We override the standard helper with our mock
        $command->getHelperSet()->set($dialog, 'dialog');

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'command'                    => $command->getName(),
            ]
        );

        $arguments = $commandTester->getInput()->getArguments();
        self::assertArrayHasKey('store-id', $arguments);
        self::assertArrayHasKey('children-categories-number', $arguments);
        self::assertArrayHasKey('category-name-prefix', $arguments);
        self::assertArrayHasKey('category-number', $arguments);
    }
}
