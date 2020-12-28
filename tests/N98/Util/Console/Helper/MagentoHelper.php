<?php

namespace N98\Util\Console\Helper;

use N98\Magento\Command\TestCase;
use org\bovigo\vfs\vfsStream;

class MagentoHelper extends TestCase
{
    /**
     * @return MagentoHelper
     */
    protected function getHelper()
    {
        $inputMock = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $outputMock = $this->createMock('Symfony\Component\Console\Output\OutputInterface');

        return new MagentoHelper($inputMock, $outputMock);
    }

    public function testHelperInstance()
    {
        self::assertInstanceOf('\N98\Util\Console\Helper\MagentoHelper', $this->getHelper());
    }

    /**
     * @test
     */
    public function detectMagentoInStandardFolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            array(
                'app' => array(
                    'Mage.php' => '',
                ),
            )
        );

        $helper = $this->getHelper();
        $helper->detect(vfsStream::url('root'), array());

        self::assertEquals(vfsStream::url('root'), $helper->getRootFolder());
        self::assertEquals(\N98\Magento\Application::MAGENTO_MAJOR_VERSION_1, $helper->getMajorVersion());
    }

    /**
     * @test
     */
    public function detectMagentoInHtdocsSubfolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            array(
                'htdocs' => array(
                    'app' => array(
                        'Mage.php' => '',
                    ),
                ),
            )
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root'),
            array(
                vfsStream::url('root/www'),
                vfsStream::url('root/public'),
                vfsStream::url('root/htdocs'),
            )
        );

        self::assertEquals(vfsStream::url('root/htdocs'), $helper->getRootFolder());
        self::assertEquals(\N98\Magento\Application::MAGENTO_MAJOR_VERSION_1, $helper->getMajorVersion());
    }

    /**
     * @test
     */
    public function detectMagentoFailed()
    {
        vfsStream::setup('root');
        vfsStream::create(
            array(
                'htdocs' => array(),
            )
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
            array(
                '.basedir' => 'root/htdocs/magento_root',
                'htdocs'   => array(
                    'magento_root' => array(
                        'app' => array(
                            'Mage.php' => '',
                        ),
                    ),
                ),
            )
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root')
        );

        // Verify if this could be checked with more elegance
        self::assertEquals(vfsStream::url('root/../root/htdocs/magento_root'), $helper->getRootFolder());

        self::assertEquals(\N98\Magento\Application::MAGENTO_MAJOR_VERSION_1, $helper->getMajorVersion());
    }

    /**
     * @test
     */
    public function detectMagento2InHtdocsSubfolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            array(
                'htdocs' => array(
                    'app' => array(
                        'autoload.php'  => '',
                        'bootstrap.php' => '',
                    ),
                ),
            )
        );

        $helper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $helper->detect(
            vfsStream::url('root'),
            array(
                vfsStream::url('root/www'),
                vfsStream::url('root/public'),
                vfsStream::url('root/htdocs'),
            )
        );

        self::assertEquals(vfsStream::url('root/htdocs'), $helper->getRootFolder());
        self::assertEquals(\N98\Magento\Application::MAGENTO_MAJOR_VERSION_2, $helper->getMajorVersion());
    }
}
