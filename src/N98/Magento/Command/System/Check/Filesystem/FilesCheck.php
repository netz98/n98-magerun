<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check\Filesystem;

use N98\Magento\Application;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use Symfony\Component\Console\Command\Command;

/**
 * Class FilesCheck
 *
 * @package N98\Magento\Command\System\Check\Filesystem
 */
class FilesCheck implements SimpleCheck, CommandAware, CommandConfigAware
{
    protected array $_commandConfig;

    protected Command $_checkCommand;

    public function check(ResultCollection $resultCollection): void
    {
        $files          = $this->_commandConfig['filesystem']['files'];
        /** @var Application $app */
        $app            = $this->_checkCommand->getApplication();
        $magentoRoot    = $app->getMagentoRootFolder();

        foreach ($files as $file => $comment) {
            $result = $resultCollection->createResult();

            if (file_exists($magentoRoot . DIRECTORY_SEPARATOR . $file)) {
                $result->setStatus(Result::STATUS_OK);
                $result->setMessage('<info>File <comment>' . $file . '</comment> found.</info>');
            } else {
                $result->setStatus(Result::STATUS_ERROR);
                $result->setMessage(
                    '<error>File ' . $file . ' not found!</error><comment> Usage: ' . $comment . '</comment>',
                );
            }
        }
    }

    public function setCommandConfig(array $commandConfig): void
    {
        $this->_commandConfig = $commandConfig;
    }

    public function setCommand(Command $command)
    {
        $this->_checkCommand = $command;
    }
}
