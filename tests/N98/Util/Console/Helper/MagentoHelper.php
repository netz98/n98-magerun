<?php

declare(strict_types=1);

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

    public function testDetectMagentoInStandardFolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['app' => ['Mage.php' => '']],
        );

        $magentoHelper = $this->getHelper();
        $magentoHelper->detect(vfsStream::url('root'), []);

        self::assertSame(vfsStream::url('root'), $magentoHelper->getRootFolder());
    }

    public function testDetectMagentoInHtdocsSubfolder()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['htdocs' => ['app' => ['Mage.php' => '']]],
        );

        $magentoHelper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $magentoHelper->detect(
            vfsStream::url('root'),
            [vfsStream::url('root/www'), vfsStream::url('root/public'), vfsStream::url('root/htdocs')],
        );

        self::assertSame(vfsStream::url('root/htdocs'), $magentoHelper->getRootFolder());
    }

    public function testDetectMagentoFailed()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['htdocs' => []],
        );

        $magentoHelper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $magentoHelper->detect(
            vfsStream::url('root'),
        );

        self::assertNull($magentoHelper->getRootFolder());
    }

    public function testDetectMagentoInModmanInfrastructure()
    {
        vfsStream::setup('root');
        vfsStream::create(
            ['.basedir' => 'root/htdocs/magento_root', 'htdocs'   => ['magento_root' => ['app' => ['Mage.php' => '']]]],
        );

        $magentoHelper = $this->getHelper();

        // vfs cannot resolve relative path so we do 'root/htdocs' etc.
        $magentoHelper->detect(
            vfsStream::url('root'),
        );

        // Verify if this could be checked with more elegance
        self::assertSame(vfsStream::url('root/../root/htdocs/magento_root'), $magentoHelper->getRootFolder());
    }
}
