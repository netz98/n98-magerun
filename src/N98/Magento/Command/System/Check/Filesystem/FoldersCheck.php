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
 * Class FoldersCheck
 *
 * @package N98\Magento\Command\System\Check\Filesystem
 */
class FoldersCheck implements SimpleCheck, CommandAware, CommandConfigAware
{
    protected array $_commandConfig;

    protected Command $_checkCommand;

    public function check(ResultCollection $resultCollection): void
    {
        $folders        = $this->_commandConfig['filesystem']['folders'];
        /** @var Application $app */
        $app            = $this->_checkCommand->getApplication();
        $magentoRoot    = $app->getMagentoRootFolder();

        foreach ($folders as $folder => $comment) {
            $result = $resultCollection->createResult();
            if (file_exists($magentoRoot . DIRECTORY_SEPARATOR . $folder)) {
                $result->setStatus(Result::STATUS_OK);
                $result->setMessage('<info>Folder <comment>' . $folder . '</comment> found.</info>');
                if (!is_writable($magentoRoot . DIRECTORY_SEPARATOR . $folder)) {
                    $result->setStatus(Result::STATUS_ERROR);
                    $result->setMessage(
                        '<error>Folder ' . $folder . ' is not writeable!</error><comment> Usage: ' . $comment .
                        '</comment>',
                    );
                }
            } else {
                $result->setStatus(Result::STATUS_ERROR);
                $result->setMessage(
                    '<error>Folder ' . $folder . ' not found!</error><comment> Usage: ' . $comment . '</comment>',
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
