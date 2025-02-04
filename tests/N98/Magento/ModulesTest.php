<?php
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
class ModulesTest extends TestCase
{
    /**
     * @test
     */
    public function creation()
    {
        $modules = new Modules();
        self::assertInstanceOf(__NAMESPACE__ . '\Modules', $modules);
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
        self::assertInstanceOf(__NAMESPACE__ . '\Modules', $result);
        self::assertCount(0, $result);
        self::assertCount(0, iterator_to_array($result));
    }

    /**
     * @test
     */
    public function findInstalledModulesAndCount()
    {
        $this->getApplication()->initMagento();
        $modules = new Modules();
        self::assertCount(0, $modules);

        $total = count($modules->findInstalledModules());
        self::assertGreaterThan(10, $total);

    }

    /**
     * @test
     */
    public function findInstalledModulesAndFilterByCodepool()
    {
        $this->getApplication()->initMagento();
        $modules = new Modules();
        $total = count($modules->findInstalledModules());

        $filtered = $modules->filterModules($this->filter('codepool', 'core'));
        self::assertLessThan($total, count($filtered));

    }

    /**
     * @test
     */
    public function findInstalledModulesAndFilterByStatus()
    {
        $this->getApplication()->initMagento();
        $modules = new Modules();
        $total = count($modules->findInstalledModules());

        $filtered = $modules->filterModules($this->filter('status', 'active'));
        self::assertLessThan($total, count($filtered));
    }

    /**
     * @test
     */
    public function findInstalledModulesAndFilterByVendor()
    {
        $this->getApplication()->initMagento();
        $modules = new Modules();
        $total = count($modules->findInstalledModules());

        $filtered = $modules->filterModules($this->filter('vendor', 'Mage_'));
        self::assertLessThan($total, count($filtered));
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

        /** @var PHPUnit_Framework_MockObject_MockObject|ArrayInput $input */
        $input = $this->getMockBuilder(ArrayInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $i = 0;
        foreach ($options as $opt => $val) {
            if (!$val) {
                continue;
            }

            $input
                ->expects(self::at($i++))
                ->method('getOption')
                ->with($opt)
                ->willReturn($val);
        }

        return $input;
    }
}
