<?php

declare(strict_types=1);

/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;
use N98\Magento\Command\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class ModulesTest
 *
 * @package N98\Magento
 * @covers N98\Magento\Modules
 */
final class ModulesTest extends TestCase
{
    public function testCreation()
    {
        $modules = new Modules();
        $this->assertInstanceOf(__NAMESPACE__ . '\Modules', $modules);
    }

    public function testFilteringCountAndIterating()
    {
        $modules = new Modules();

        $result = $modules->filterModules(
            $this->filter(),
        );
        $this->assertInstanceOf(__NAMESPACE__ . '\Modules', $result);
        $this->assertEmpty($result);
        $this->assertEmpty(iterator_to_array($result));
    }

    public function testFindInstalledModulesAndFilterThem()
    {
        $this->getApplication()->initMagento();

        $modules = new Modules();
        $this->assertEmpty($modules);
        $total = count($modules->findInstalledModules());
        $this->assertGreaterThan(10, $total);

        $filtered = $modules->filterModules($this->filter('codepool', 'core'));
        $this->assertLessThan($total, count($filtered));

        $filtered = $modules->filterModules($this->filter('status', 'active'));
        $this->assertLessThan($total, count($filtered));

        $filtered = $modules->filterModules($this->filter('vendor', 'Mage_'));
        $this->assertLessThan($total, count($filtered));
    }

    /**
     * Helper method to create a fake input
     *
     * @param string $option
     * @param string $value
     * @return MockObject|ArrayInput
     */
    private function filter($option = null, $value = null)
    {
        $defaultOptions = ['codepool' => false, 'status' => false, 'vendor' => false];
        $options = $defaultOptions;

        if (null !== $option) {
            if (!array_key_exists($option, $defaultOptions)) {
                throw new InvalidArgumentException(sprintf('Invalid option "%s"', $option));
            }

            $options[$option] = $value;
        }

        /** @var $input PHPUnit_Framework_MockObject_MockObject|ArrayInput */
        $input = $this->getMockBuilder(ArrayInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $i = 0;
        foreach ($options as $opt => $val) {
            $input
                ->expects(self::at($i++))
                ->method('getOption')
                ->with($opt)
                ->willReturn($val);

            if (!$val) {
                continue;
            }

            $input->expects(self::at($i++))
                ->method('getOption')
                ->with($opt)
                ->willReturn($val);
        }

        return $input;
    }
}
