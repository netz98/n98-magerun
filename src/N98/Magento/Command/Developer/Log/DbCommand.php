<?php

declare(strict_types=1);

namespace N98\Magento\Command\Developer\Log;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
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
    public const COMMAND_OPTION_OFF = 'off';

    public const COMMAND_OPTION_ON = 'on';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'dev:log:db';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Turn on/off database query logging';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_ON,
                null,
                InputOption::VALUE_NONE,
                'Force logging'
            )
            ->addOption(
                self::COMMAND_OPTION_OFF,
                null,
                InputOption::VALUE_NONE,
                'Disable logging'
            )
        ;
    }

    /**
     * @return string
     */
    protected function _getVarienAdapterPhpFile(): string
    {
        return $this->_magentoRootFolder . '/lib/Varien/Db/Adapter/Pdo/Mysql.php';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $output->writeln("<info>Looking in " . $this->_getVarienAdapterPhpFile() . "</info>");

        $this->_replaceVariable($input, $output, '$_debug');
        $this->_replaceVariable($input, $output, '$_logAllQueries');

        $output->writeln("<info>Done. You can tail <comment>" . $this->_getDebugLogFilename() . "</comment></info>");

        return Command::SUCCESS;
    }

    /**
     * @return string
     * @todo I believe 1.5 and under put this in a different filename.
     */
    protected function _getDebugLogFilename(): string
    {
        return 'var/debug/pdo_mysql.log';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $variable
     * @return void
     */
    protected function _replaceVariable(InputInterface $input, OutputInterface $output, string $variable): void
    {
        $varienAdapterPhpFile = $this->_getVarienAdapterPhpFile();
        $contents = file_get_contents($varienAdapterPhpFile);
        if (!$contents) {
            $contents = '';
        }

        $debugLinePattern = "/protected\\s" . '\\' . $variable . "\\s*?=\\s(false|true)/m";
        preg_match($debugLinePattern, $contents, $matches);
        if (!isset($matches[1])) {
            throw new RuntimeException("Problem finding the \$_debug parameter");
        }

        $currentValue = $matches[1];
        if ($input->getOption(self::COMMAND_OPTION_OFF)) {
            $newValue = 'false';
        } elseif ($input->getOption(self::COMMAND_OPTION_ON)) {
            $newValue = 'true';
        } else {
            $newValue = ($currentValue == 'false') ? 'true' : 'false';
        }

        $output->writeln(
            "<info>Changed <comment>" . $variable . "</comment> to <comment>" . $newValue . "</comment></info>"
        );

        $contents = preg_replace($debugLinePattern, "protected " . $variable . " = " . $newValue, $contents);
        file_put_contents($varienAdapterPhpFile, $contents);
    }
}
