<?php

namespace N98\Magento\Command\Script\Repository;

use InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractRepositoryCommand
{
    protected function configure()
    {
        $help = <<<HELP
Please note that the script repo command runs only scripts which are stored
in a defined script folder.

Script folders can defined by config.

Example:

script:
  folders:
    - /my/script_folder


There are some pre defined script folders:

- /usr/local/share/n98-magerun/scripts
- ~/.n98-magerun/scripts

If you like to run a standalone script you can also use the "script" command.

See: n98-magerun.phar script <filename.magerun>

HELP;

        $this
            ->setName('script:repo:run')
            ->addArgument('script', InputArgument::OPTIONAL, 'Name of script in repository')
            ->addOption('define', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Defines a variable')
            ->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stops execution of script on error')
            ->setDescription('Run script from repository')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->getScripts();
        if ($input->getArgument('script') == null && $input->isInteractive()) {
            $question = array();
            $i = 0;
            foreach ($files as $file) {
                $files[$i] = $file;
                $question[] = '<comment>[' . ($i + 1) . ']</comment> ' . $file['fileinfo']->getFilename() . PHP_EOL;
                $i++;
            }

            $question[] = '<question>Please select a script file: </question>';
            $selectedFile = $this->getHelper('dialog')->askAndValidate(
                $output,
                $question,
                function ($typeInput) use ($files) {
                    if (!isset($files[$typeInput - 1])) {
                        throw new InvalidArgumentException('Invalid file');
                    }

                    return $files[$typeInput - 1]['fileinfo']->getPathname();
                }
            );
        } else {
            $script = $input->getArgument('script');
            if (substr($script, -strlen(self::MAGERUN_EXTENSION)) !== self::MAGERUN_EXTENSION) {
                $script .= self::MAGERUN_EXTENSION;
            }

            if (!isset($files[$script])) {
                throw new InvalidArgumentException('Invalid script');
            }
            $selectedFile = $files[$script]['fileinfo']->getPathname();
        }

        $scriptArray = array(
            'command'  => 'script',
            'filename' => $selectedFile,
        );
        foreach ($input->getOption('define') as $define) {
            $scriptArray['--define'][] = $define;
        }
        if ($input->getOption('stop-on-error')) {
            $scriptArray['--stop-on-error'] = true;
        }
        $input = new ArrayInput($scriptArray);
        $this->getApplication()->run($input, $output);
    }
}
