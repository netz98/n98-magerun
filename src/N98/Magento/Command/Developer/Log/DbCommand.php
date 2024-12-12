<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Toggle database log command
 *
 * @package N98\Magento\Command\Developer\Log
 */
class DbCommand extends AbstractLogCommand
{
    protected function configure(): void
    {
        $this
            ->setName('dev:log:db')
            ->addOption('on', null, InputOption::VALUE_NONE, 'Force logging')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setDescription('Turn on/off database query logging');
    }

    protected function _getVarienAdapterPhpFile(): string
    {
        return $this->_magentoRootFolder . '/lib/Varien/Db/Adapter/Pdo/Mysql.php';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $output->writeln('<info>Looking in ' . $this->_getVarienAdapterPhpFile() . '</info>');

        $this->_replaceVariable($input, $output, '$_debug');
        $this->_replaceVariable($input, $output, '$_logAllQueries');

        $output->writeln('<info>Done. You can tail <comment>' . $this->_getDebugLogFilename() . '</comment></info>');

        return Command::SUCCESS;
    }

    /**
     * @todo I believe 1.5 and under put this in a different filename.
     */
    protected function _getDebugLogFilename(): string
    {
        return 'var/debug/pdo_mysql.log';
    }

    protected function _replaceVariable(InputInterface $input, OutputInterface $output, string $variable): void
    {
        $varienAdapterPhpFile   = $this->_getVarienAdapterPhpFile();
        $contents               = (string) file_get_contents($varienAdapterPhpFile);

        $debugLinePattern = '/protected\s\\' . $variable . '\\s*?=\\s(false|true)/m';
        preg_match($debugLinePattern, $contents, $matches);
        if (!isset($matches[1])) {
            throw new RuntimeException('Problem finding the $_debug parameter');
        }

        $currentValue = $matches[1];
        if ($input->getOption('off')) {
            $newValue = 'false';
        } elseif ($input->getOption('on')) {
            $newValue = 'true';
        } else {
            $newValue = ($currentValue === 'false') ? 'true' : 'false';
        }

        $output->writeln(
            '<info>Changed <comment>' . $variable . '</comment> to <comment>' . $newValue . '</comment></info>',
        );

        $contents = preg_replace($debugLinePattern, 'protected ' . $variable . ' = ' . $newValue, $contents);
        file_put_contents($varienAdapterPhpFile, $contents);
    }
}
