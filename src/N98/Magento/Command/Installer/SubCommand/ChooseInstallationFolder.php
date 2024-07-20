<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use Symfony\Component\Console\Question\Question;

/**
 * Class ChooseInstallationFolder
 *
 * @package N98\Magento\Command\Installer\SubCommand
 */
class ChooseInstallationFolder extends AbstractSubCommand
{
    /**
     * @return bool
     */
    public function execute()
    {
        $input = $this->input;
        $validateInstallationFolder = function ($folderName) {
            $folderName = rtrim(trim($folderName, ' '), '/');
            if ($folderName[0] === '.') {
                $cwd = \getcwd();
                if (empty($cwd) && isset($_SERVER['PWD'])) {
                    $cwd = $_SERVER['PWD'];
                }
                $folderName = $cwd . substr($folderName, 1);
            }

            if (empty($folderName)) {
                throw new \InvalidArgumentException('Installation folder cannot be empty');
            }

            if (!is_dir($folderName)) {
                if (!mkdir($folderName, 0777, true) && !is_dir($folderName)) {
                    throw new \InvalidArgumentException('Cannot create folder.');
                }

                return $folderName;
            }

            return $folderName;
        };

        $installationFolder = $input->getOption('installationFolder');
        if ($installationFolder === null) {
            $defaultFolder = './magento';
            $question = new Question(
                sprintf(
                    '<question>Enter installation folder:</question> [<comment>%s</comment>]',
                    $defaultFolder
                ),
                $defaultFolder
            );
            $question->setValidator($validateInstallationFolder);

            $installationFolder = $this->getCommand()->getHelper('question')->ask(
                $this->input,
                $this->output,
                $question
            );
        } else {
            // @Todo improve validation and bring it to 1 single function
            $installationFolder = $validateInstallationFolder($installationFolder);
        }

        $this->config->setString('initialFolder', getcwd());
        $this->config->setString('installationFolder', realpath($installationFolder));
        \chdir($this->config->getString('installationFolder'));

        return true;
    }
}
