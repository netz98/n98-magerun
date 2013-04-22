<?php

namespace N98\Magento\Command;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\OperatingSystem;
use Composer\Util\RemoteFilesystem;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class SelfUpdateCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->setDescription('Updates n98-magerun.phar to the latest version.')
            ->setHelp(<<<EOT
The <info>self-update</info> command checks github for newer
versions of n98-magerun and if found, installs the latest.

<info>php n98-magerun.phar self-update</info>

EOT
            )
        ;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getApplication()->isPharMode();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $rfs = new RemoteFilesystem($io);
        $latest = trim($rfs->getContents('raw.github.com', 'https://raw.github.com/netz98/n98-magerun/master/version.txt', false));

        if ($this->getApplication()->getVersion() !== $latest) {
            $output->writeln(sprintf("Updating to version <info>%s</info>.", $latest));

            $os = new OperatingSystem();
            if ($os->isWindows()) {
                $remoteFilename = 'https://raw.github.com/netz98/n98-magerun/master/n98-magerun-windows.phar';
            } else {
                $remoteFilename = 'https://raw.github.com/netz98/n98-magerun/master/n98-magerun.phar';
            }

            $localFilename = $_SERVER['argv'][0];
            if (!is_writable($localFilename)) {
                throw new \RuntimeException('phar is not writeable. Please change permissions or run as root or with sudo.');
            }

            $tempFilename = basename($localFilename, '.phar').'-temp.phar';

            $rfs->copy('raw.github.com', $remoteFilename, $tempFilename);

            try {
                @chmod($tempFilename, 0777 & ~umask());
                // test the phar validity
                $phar = new \Phar($tempFilename);
                // free the variable to unlock the file
                unset($phar);
                @rename($tempFilename, $localFilename);
                $output->writeln('<info>Successfully updated n98-magerun</info>');

                $changeLogContent = $rfs->getContents('raw.github.com', 'https://raw.github.com/netz98/n98-magerun/master/changes.txt', false);
                if ($changeLogContent) {
                    $output->writeln($changeLogContent);
                }

            } catch (\Exception $e) {
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                unlink($tempFilename);
                $output->writeln('<error>The download is corrupted ('.$e->getMessage().').</error>');
                $output->writeln('<error>Please re-run the self-update command to try again.</error>');
            }
        } else {
            $output->writeln("<info>You are using the latest n98-magerun version.</info>");
        }
    }
}
