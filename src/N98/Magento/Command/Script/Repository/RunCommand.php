<?php

namespace N98\Magento\Command\Script\Repository;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Shell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractRepositoryCommand
{
    const MAGERUN_EXTENSION = '.magerun';

    protected function configure()
    {
        $this
            ->setName('script:repo:run')
            ->addArgument('script', InputArgument::OPTIONAL, 'Name of script in repository')
            ->setDescription('Run script from repository')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->getScripts();
        if ($input->getArgument('script') == null) {
            $question = array();
            $i = 0;
            foreach ($files as $file) {
                $files[$i] = $file;
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $file['fileinfo']->getFilename() . PHP_EOL;
                $i++;
            }
            $question[] = '<question>Please select a script file: </question>';
            $selectedFile = $this->getHelper('dialog')->askAndValidate($output, $question, function($typeInput) use ($files) {
                if (!isset($files[$typeInput - 1])) {
                    throw new \InvalidArgumentException('Invalid file');
                }

                return $files[$typeInput - 1]['fileinfo']->getPathname();
            });
        } else {
            $script = $input->getArgument('script');
            if (substr($script, -strlen(self::MAGERUN_EXTENSION)) !== self::MAGERUN_EXTENSION) {
                $script .= self::MAGERUN_EXTENSION;
            }

            if (!isset($files[$script])) {
                throw new \InvalidArgumentException('Invalid script');
            }
            $selectedFile = $files[$script]['fileinfo']->getPathname();
        }

        $input = new StringInput('script ' . escapeshellarg($selectedFile));
        $this->getApplication()->run($input, $output);
    }
}