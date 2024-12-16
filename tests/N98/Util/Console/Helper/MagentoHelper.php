<?php

namespace N98\Util\Console\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Magento\Application;
use N98\Magento\Command\TestCase;
use org\bovigo\vfs\vfsStream;

class MagentoHelper extends TestCase
{
    /**
     * @return MagentoHelper
     */
    protected function getHelper()
    {
        $inputMock = $this->createMock(InputInterface::class);
        $outputMock = $this->createMock(OutputInterface::class);

        return new MagentoHelper($inputMock, $outputMock);
    }

    public function testHelperInstance()
    {
        self::assertInstanceOf(\N98\Util\Console\Helper\MagentoHelper::class, $this->getHelper());
    }

    /**
     * @test
     */
    public function detectMagentoInStandardFolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['app' => ['Mage.php' => '']]
        );

        $helper = $this->getHelper();
        $helper->detect(vfsStream::url('root'), []);

        self::assertEquals(vfsStream::url('root'), $helper->getRootFolder());
    }

    /**
     * @test
     */
    public function detectMagentoInHtdocsSubfolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['htdocs' => ['app' => ['Mage.php' => '']]]
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root'),
            [vfsStream::url('root/www'), vfsStream::url('root/public'), vfsStream::url('root/htdocs')]
        );

        self::assertEquals(vfsStream::url('root/htdocs'), $helper->getRootFolder());
    }

    /**
     * @test
     */
    public function detectMagentoFailed()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['htdocs' => []]
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root')
        );

        self::assertNull($helper->getRootFolder());
    }

    /**
     * @test
     */
    public function detectMagentoInModmanInfrastructure()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['.basedir' => 'root/htdocs/magento_root', 'htdocs'   => ['magento_root' => ['app' => ['Mage.php' => '']]]]
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root')
        );

        // Verify if this could be checked with more elegance
        self::assertEquals(vfsStream::url('root/../root/htdocs/magento_root'), $helper->getRootFolder());
    }
}
