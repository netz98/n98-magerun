<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use Exception;
use Mage;
use Mage_Core_Model_Resource_Setup;
use N98\Magento\Command\AbstractMagentoCommand;
use ReflectionObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function file;
use function ob_end_clean;
use function ob_start;

/**
 * Run setup command
 *
 * @package N98\Magento\Command\System\Setup
 */
class RunCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:setup:run')
            ->addOption(
                '--no-implicit-cache-flush',
                null,
                InputOption::VALUE_NONE,
                'Do not flush the cache',
            )
            ->setDescription('Runs all new setup scripts.');
    }

    public function getHelp(): string
    {
        return <<<HELP
Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        try {
            if (false === $input->getOption('no-implicit-cache-flush')) {
                $this->flushCache();
            }

            /**
             * Put output in buffer. \Mage_Core_Model_Resource_Setup::_modifyResourceDb should print any error
             * directly to stdout. Use exception which will be thrown to show error
             */
            ob_start();
            Mage_Core_Model_Resource_Setup::applyAllUpdates();
            if (is_callable(['\Mage_Core_Model_Resource_Setup', 'applyAllDataUpdates'])) {
                Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
            }

            ob_end_clean();
            $output->writeln('<info>done</info>');
        } catch (Exception $exception) {
            ob_end_clean();
            $this->getApplication()->renderThrowable($exception, $output);
            $this->printStackTrace($output, $exception);
            $this->printFile($output, $exception);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     *
     * @return void
     */
    protected function printStackTrace(OutputInterface $output, Exception $exception)
    {
        $rootFolder = $this->getApplication()->getMagentoRootFolder();
        $trace = array_filter($exception->getTrace(), function (&$row) use ($rootFolder) {
            if (in_array(strstr($row['file'], $rootFolder), ['', '0'], true) || strstr($row['file'], $rootFolder) === false) {
                return false;
            }

            $row['file'] = ltrim(str_replace($rootFolder, '', $row['file']), '/');

            return $row;
        });

        $tableHelper = $this->getTableHelper();
        $rows = [];
        $i = 1;
        foreach ($trace as $row) {
            $rows[] = [$i++, $row['file'] . ':' . $row['line'], $row['class'] . '::' . $row['function']];
        }

        $tableHelper->setHeaders(['#', 'File/Line', 'Method']);
        $tableHelper->setRows($rows);
        $tableHelper->render($output);
    }

    protected function printFile(OutputInterface $output, Exception $exception): void
    {
        if (preg_match('/Error\sin\sfile\:\s"(.+)\"\s-/', $exception->getMessage(), $matches)) {
            $lines  = file($matches[1]);
            $rows   = [];
            if ($lines) {
                $i = 0;
                foreach ($lines as $line) {
                    $rows[] = [++$i, rtrim($line)];
                }
            }

            $tableHelper = $this->getTableHelper();
            $tableHelper->setHeaders(['Line', 'Code']);
            $tableHelper->setRows($rows);
            $tableHelper->render($output);
        }
    }

    private function flushCache(): void
    {
        /**
         * Get events before cache flush command is called.
         */
        $reflectionObject = new ReflectionObject(Mage::app());
        $appEventReflectionProperty = $reflectionObject->getProperty('_events');
        $appEventReflectionProperty->setAccessible(true);

        $eventsBeforeCacheFlush = $appEventReflectionProperty->getValue(Mage::app());

        $application = $this->getApplication();
        $saved = $application->setAutoExit(false);
        $application->run(new StringInput('cache:flush'), new NullOutput());
        $application->setAutoExit($saved);

        /**
         * Restore initially loaded events which was reset during setup script run
         */
        $appEventReflectionProperty->setValue(Mage::app(), $eventsBeforeCacheFlush);
    }
}
