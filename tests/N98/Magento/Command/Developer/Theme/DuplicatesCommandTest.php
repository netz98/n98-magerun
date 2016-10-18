<?php

namespace N98\Magento\Command\Developer\Theme;

use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DuplicatesCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new DuplicatesCommand());
        $command = $this->getApplication()->find('dev:theme:duplicates');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'theme'         => 'base/default',
                'originalTheme' => 'base/default',
            )
        );

        $display = $commandTester->getDisplay();

        $this->assertContainsPath('template/catalog/product/price.phtml', $display);
        $this->assertContainsPath('layout/catalog.xml', $display);
        $this->assertNotContains('No duplicates was found', $display);
    }

    /**
     * @param string $path     POSIX path to search for (directory separator is <slash>)
     * @param string $haystack to search in can contain POSIX path or DOS path (directory separator is <backslash>)
     */
    private function assertContainsPath($path, $haystack)
    {
        // turn path parameter into a regular expression that allows on of two directory separators: <slash> and <backslash>
        $segments = preg_split('~/~', $path);

        $separator = '([/\\\\])';

        $segmentCount = 0;
        $pattern = '~';
        while ($segment = array_shift($segments)) {
            $pattern .= preg_quote($segment, '~');
            if ($segments) {
                $pattern .= $segmentCount++ ? '\\1' : $separator;
            }
        }
        $pattern .= '~';

        $this->assertRegExp($pattern, $haystack);
    }
}
