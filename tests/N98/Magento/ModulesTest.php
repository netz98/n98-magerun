<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

namespace N98\Magento;

use InvalidArgumentException;
use N98\Magento\Command\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class ModulesTest
 *
 * @package N98\Magento
 * @covers N98\Magento\Modules
 */
class ModulesTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $modules = new Modules();
        $this->assertInstanceOf(__NAMESPACE__ . '\Modules', $modules);
    }

    /**
     * @test
     */
    public function filteringCountAndIterating()
    {
        $modules = new Modules();

        $result = $modules->filterModules(
            $this->filter()
        );
        $this->assertInstanceOf(__NAMESPACE__ . '\Modules', $result);
        $this->assertCount(0, $result);
        $this->assertCount(0, iterator_to_array($result));
    }

    /**
     * @test
     */
    public function findInstalledModulesAndFilterThem()
    {
        $this->getApplication()->initMagento();

        $modules = new Modules();
        $this->assertCount(0, $modules);
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
     * @return PHPUnit_Framework_MockObject_MockObject|ArrayInput
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
        $input = $this->getMockBuilder(\Symfony\Component\Console\Input\ArrayInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $i = 0;
        foreach ($options as $opt => $val) {
            $input
                ->expects($this->at($i++))
                ->method('getOption')
                ->with($opt)
                ->willReturn($val);

            if (!$val) {
                continue;
            }

            $input->expects($this->at($i++))
                ->method('getOption')
                ->with($opt)
                ->willReturn($val);
        }

        return $input;
    }
}
