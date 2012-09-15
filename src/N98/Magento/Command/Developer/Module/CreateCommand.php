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
            ->addOption('add-blocks', null, InputOption::VALUE_NONE, 'Adds blocks')
            ->addOption('add-helpers', null, InputOption::VALUE_NONE, 'Adds helpers')
            ->addOption('add-models', null, InputOption::VALUE_NONE, 'Adds models')
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
        $this->baseFolder = __DIR__ . '/../../../../../../res/module/create';
        $this->vendorNamespace = ucfirst($input->getArgument('vendorNamespace'));
        $this->moduleName = ucfirst($input->getArgument('moduleName'));
        $this->codePool = $input->getArgument('codePool');
        if (!in_array($this->codePool, array('local', 'community'))) {
            throw new \InvalidArgumentException('Code pool must "community" or "local"');
        }
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
        $view->assign('createBlocks', $input->getOption('add-blocks'));
        $view->assign('createModels', $input->getOption('add-models'));
        $view->assign('createHelpers', $input->getOption('add-helpers'));
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
        $this->moduleDirectory = $moduleDir;
        mkdir($this->moduleDirectory, 0777, true);
        $output->writeln('<info>Created directory: <comment>' .  $this->moduleDirectory .'<comment></info>');

        // Add etc folder
        mkdir($this->moduleDirectory . '/etc');
        $output->writeln('<info>Created directory: <comment>' .  $this->moduleDirectory .'/etc<comment></info>');

        // Add blocks folder
        if ($input->getOption('add-blocks')) {
            mkdir($this->moduleDirectory . '/Block');
            $output->writeln('<info>Created directory: <comment>' .  $this->moduleDirectory . '/Block' .'<comment></info>');
        }

        // Add helpers folder
        if ($input->getOption('add-helpers')) {
            mkdir($this->moduleDirectory . '/Helper');
            $output->writeln('<info>Created directory: <comment>' .  $this->moduleDirectory . '/Helper' .'<comment></info>');
        }

        // Add models folder
        if ($input->getOption('add-models')) {
            mkdir($this->moduleDirectory . '/Model');
            $output->writeln('<info>Created directory: <comment>' .  $this->moduleDirectory . '/Model' .'<comment></info>');
        }
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
