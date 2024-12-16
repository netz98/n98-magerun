<?php

namespace N98\Magento\Command\System\Url;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('sys:url:list');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command'          => $command->getName(),
                'stores'           => 0,
                // admin store
                'linetemplate'     => 'prefix {url} suffix',
                '--add-categories' => true,
                '--add-products'   => true,
                '--add-cmspages'   => true,
            ]
        );

        self::assertMatchesRegularExpression('/prefix/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/http/', $commandTester->getDisplay());
        self::assertMatchesRegularExpression('/suffix/', $commandTester->getDisplay());
    }
}
