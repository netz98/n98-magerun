<?php

namespace N98\Magento\Command\Developer\Module;

use N98\Magento\Command\AbstractMagentoCommand;
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
     * @var array
     */
    protected $twigVars = array();

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

    /**
     * @var bool
     */
    protected $modmanMode = false;

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
            ->addOption('add-setup', null, InputOption::VALUE_NONE, 'Adds SQL setup')
            ->addOption('add-all', null, InputOption::VALUE_NONE, 'Adds blocks, helpers and models')
            ->addOption('modman', null, InputOption::VALUE_NONE, 'Create all files in folder with a modman file.')
            ->addOption('add-readme', null, InputOption::VALUE_NONE, 'Adds a readme.md file to generated module')
            ->addOption('add-composer', null, InputOption::VALUE_NONE, 'Adds a composer.json file to generated module')
            ->addOption('author-name', null, InputOption::VALUE_OPTIONAL, 'Author for readme.md or composer.json')
            ->addOption('author-email', null, InputOption::VALUE_OPTIONAL, 'Author for readme.md or composer.json')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Description for readme.md or composer.json')
            ->setDescription('Create and register a new magento module.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->modmanMode = $input->getOption('modman');
        if ($input->getOption('add-all')) {
            $input->setOption('add-blocks', true);
            $input->setOption('add-helpers', true);
            $input->setOption('add-models', true);
            $input->setOption('add-setup', true);
            $input->setOption('add-readme', true);
            $input->setOption('add-composer', true);
        }
        if (!$this->modmanMode) {
            $this->detectMagento($output);
        }
        $this->baseFolder = __DIR__ . '/../../../../../../res/module/create';

        $this->vendorNamespace = ucfirst($input->getArgument('vendorNamespace'));
        $this->moduleName = ucfirst($input->getArgument('moduleName'));
        $this->codePool = $input->getArgument('codePool');
        if (!in_array($this->codePool, array('local', 'community'))) {
            throw new \InvalidArgumentException('Code pool must "community" or "local"');
        }
        $this->initView($input);
        $this->createModuleDirectories($input, $output);
        $this->writeEtcModules($output);
        $this->writeModuleConfig($output);
        $this->writeReadme($input, $output);
        if ($this->modmanMode) {
            $this->writeModmanFile($output);
        }
        $this->writeComposerConfig($input, $output);
        $this->addAdditionalFiles($output);
    }

    protected function initView(InputInterface $input)
    {
        $this->twigVars = array(
            'vendorNamespace' => $this->vendorNamespace,
            'moduleName'      => $this->moduleName,
            'codePool'        => $this->codePool,
            'createBlocks'    => $input->getOption('add-blocks'),
            'createModels'    => $input->getOption('add-models'),
            'createHelpers'   => $input->getOption('add-helpers'),
            'createSetup'     => $input->getOption('add-setup'),
            'authorName'      => $input->getOption('author-name'),
            'authorEmail'     => $input->getOption('author-email'),
            'description'     => $input->getOption('description'),
        );
    }

    protected function createModuleDirectories(InputInterface $input, OutputInterface $output)
    {
        if ($this->modmanMode) {
            $modManDir = $this->vendorNamespace . '_' . $this->moduleName. '/src';
            if (file_exists($modManDir)) {
                throw new \RuntimeException('Module already exists. Stop.');
            }
            mkdir($modManDir, 0777, true);
            $this->_magentoRootFolder = './' . $modManDir;
            mkdir($this->_magentoRootFolder . '/app/etc/modules', 0777, true);
        }
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

        // Create SQL and Data folder
        if ($input->getOption('add-setup')) {
            $sqlSetupFolder = $this->moduleDirectory . '/sql/' . strtolower($this->vendorNamespace) . '_' . strtolower($this->moduleName) . '_setup';
            mkdir($sqlSetupFolder, 0777, true);
            $output->writeln('<info>Created directory: <comment>' . $sqlSetupFolder . '<comment></info>');

            $dataSetupFolder = $this->moduleDirectory . '/data/' . strtolower($this->vendorNamespace) . '_' . strtolower($this->moduleName) . '_setup';
            mkdir($dataSetupFolder, 0777, true);
            $output->writeln('<info>Created directory: <comment>' . $dataSetupFolder . '<comment></info>');

        }
    }

    protected function writeEtcModules(OutputInterface $output)
    {
        $outFile = $this->_magentoRootFolder
                 . '/app/etc/modules/'
                 . $this->vendorNamespace
                 . '_'
                 . $this->moduleName
                 . '.xml';
        file_put_contents(
            $outFile,
            $this->getHelper('twig')->render('dev/module/create/app/etc/modules/definition.twig', $this->twigVars)
        );
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    protected function writeModuleConfig(OutputInterface $output)
    {
        $outFile = $this->moduleDirectory . '/etc/config.xml';
        file_put_contents(
            $outFile,
            $this->getHelper('twig')->render('dev/module/create/app/etc/modules/config.twig', $this->twigVars)
        );

        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    protected function writeModmanFile(OutputInterface $output)
    {
        $outFile = $this->_magentoRootFolder . '/../modman';
        file_put_contents(
            $outFile,
            $this->getHelper('twig')->render('dev/module/create/modman.twig', $this->twigVars)
        );
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    /**
     * Write standard readme
     *
     * TODO: Make author name / company URL and more configurable
     *
     * @see https://raw.github.com/sprankhub/Magento-Extension-Sample-Readme/master/readme.markdown
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function writeReadme($input, $output)
    {
        if (!$input->getOption('add-readme')) {
            return;
        }
        if ($this->modmanMode) {
            $outFile = $this->_magentoRootFolder . '/../readme.md';
        } else {
            $outFile = $this->moduleDirectory . '/etc/readme.md';
        }
        file_put_contents(
            $outFile,
            $this->getHelper('twig')->render('dev/module/create/app/etc/modules/readme.twig', $this->twigVars)
        );
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    /**
     * Write composer.json
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function writeComposerConfig(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('add-composer')) {
            return;
        }
        if ($this->modmanMode) {
            $outFile = $this->_magentoRootFolder . '/../composer.json';
        } else {
            $outFile = $this->moduleDirectory . '/etc/composer.json';
        }
        file_put_contents(
            $outFile,
            $this->getHelper('twig')->render('dev/module/create/composer.twig', $this->twigVars)
        );
        $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
    }

    protected function addAdditionalFiles(OutputInterface $output)
    {
        $config = $this->getCommandConfig();
        if (isset($config['additionalFiles']) && is_array($config['additionalFiles'])) {
            foreach ($config['additionalFiles'] as $template => $outFileRaw) {
                $outFile = $this->_getOutfile($outFileRaw);
                if (!is_dir(dirname($outFile))) {
                    mkdir(dirname($outFile), 0777, true);
                }
                file_put_contents(
                    $outFile,
                    $this->getHelper('twig')->render($template, $this->twigVars)
                );
                $output->writeln('<info>Created file: <comment>' .  $outFile .'<comment></info>');
            }

        }
    }

    /**
     * @param string $filename
     * @return string
     */
    private function _getOutfile($filename)
    {
        $pathes = array(
            'rootDir'   => $this->_magentoRootFolder,
            'moduleDir' => $this->moduleDirectory,
        );

        return $this->getHelper('twig')->renderString($filename, array_merge($this->twigVars, $pathes));
    }
}
