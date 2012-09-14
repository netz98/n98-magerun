<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\View\PhpView;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create a magento module skeleton
 */
class CreateCommand extends AbstractMagentoCommand
{
    /**
     * @var PhpView
     */
    protected $view;

    /**
     * @var string
     */
    protected $baseFolder;

    /**
     * @var string
     */
    protected $moduleDirectory;

    /**
     * @var string
     */
    protected $vendorNamespace;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var string
     */
    protected $codePool;

    protected function configure()
    {
        $this
            ->setName('dev:module:create')
            ->addArgument('vendorNamespace', InputArgument::REQUIRED, 'Namespace (your company prefix)')
            ->addArgument('moduleName', InputArgument::REQUIRED, 'Name of your module.')
            ->addArgument('codePool', InputArgument::OPTIONAL, 'Codepool (local,community)', 'local')
            ->setDescription('Creates an registers new magento module.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        $this->baseFolder = '../../../../../../res/module/create';
        $this->vendorNamespace = $input->getArgument('vendorNamespace');
        $this->moduleName = $input->getArgument('moduleName');
        $this->codePool = $input->getArgument('codePool');
        $this->initView($input);
        $this->createModuleDirectories($input, $output);
        $this->writeEtcModules($input, $output);
        $this->writeModuleConfig($input, $output);
    }

    protected function initView($input)
    {
        $view = new PhpView();
        $view->assign('vendorNamespace', $this->vendorNamespace);
        $view->assign('moduleName', $this->moduleName);
        $view->assign('codePool', $this->codePool);
        $this->view = $view;
    }

    protected function createModuleDirectories($input, $output)
    {
        $moduleDir = $this->_magentoRootFolder
                   . '/app/code/'
                   . $this->codePool
                   . '/' . $this->vendorNamespace
                   . '/' . $this->moduleName;
        if (file_exists($moduleDir)) {
            throw new \RuntimeException('Module already exists. Stop.');
        }
        mkdir($moduleDir . '/etc', 0777, true);
        $this->moduleDirectory = $moduleDir;
        $output->writeln('<info>Created directory: <comment>' .  $moduleDir .'<comment></info>');
    }

    protected function writeEtcModules($input, $output)
    {
        $this->view->setTemplate($this->baseFolder . '/app/etc/modules/definition.phtml');
        $outFile = $this->_magentoRootFolder
                 . '/app/etc/modules/'
                 . $this->vendorNamespace
                 . '_'
                 . $this->moduleName
                 . '.xml';
        file_put_contents($outFile, $this->view->render());
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    protected function writeModuleConfig($input, $output)
    {
        $this->view->setTemplate($this->baseFolder . '/app/etc/modules/config.phtml');
        $outFile = $this->moduleDirectory . '/etc/config.xml';
        file_put_contents($outFile, $this->view->render());
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }
}
