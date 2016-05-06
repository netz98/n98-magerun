<?php
/**
 * this file is part of magerun
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */
namespace N98\Magento;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Console\Input\ArrayInput;

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
    public function filtering()
    {
        $modules = new Modules();

        /** @var $input PHPUnit_Framework_MockObject_MockObject|ArrayInput */
        $input = $this->getMock('Symfony\Component\Console\Input\ArrayInput', array('getOption'), array(), '', false);
        $input->method('getOption')->willReturn(false);

        $result = $modules->filterModules($input);
        $this->assertInstanceOf(__NAMESPACE__ . '\Modules', $result);
    }
}
