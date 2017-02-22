<?php

namespace N98\Magento\Command\System\Setup;

use Exception;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:run')
            ->addOption(
                '--no-implicit-cache-flush',
                null,
                InputOption::VALUE_NONE,
                'Do not flush the cache'
            )
            ->setDescription('Runs all new setup scripts.');
        $help = <<<HELP
Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        try {
            if (false === $input->getOption('no-implicit-cache-flush')) {
                $this->flushCache();
            }

            /**
             * Put output in buffer. \Mage_Core_Model_Resource_Setup::_modifyResourceDb should print any error
             * directly to stdout. Use exception which will be thrown to show error
             */
            \ob_start();
            \Mage_Core_Model_Resource_Setup::applyAllUpdates();
            if (is_callable(array('\Mage_Core_Model_Resource_Setup', 'applyAllDataUpdates'))) {
                \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
            }
            \ob_end_clean();
            $output->writeln('<info>done</info>');
        } catch (Exception $e) {
            \ob_end_clean();
            $this->getApplication()->renderException($e, $output);
            $this->printStackTrace($output, $e);
            $this->printFile($output, $e);

            return 1; // exit with error status
        }
    }

    /**
     * @param OutputInterface $output
     * @param Exception $e
     *
     * @return void
     */
    protected function printStackTrace(OutputInterface $output, Exception $e)
    {
        $rootFolder = $this->getApplication()->getMagentoRootFolder();
        $trace = array_filter($e->getTrace(), function (&$row) use ($rootFolder) {
            if (!strstr($row['file'], $rootFolder)) {
                return false;
            }

            $row['file'] = ltrim(str_replace($rootFolder, '', $row['file']), '/');

            return $row;
        });

        /* @var $tableHelper TableHelper */
        $tableHelper = $this->getHelper('table');
        $rows = array();
        $i = 1;
        foreach ($trace as $row) {
            $rows[] = array(
                $i++,
                $row['file'] . ':' . $row['line'],
                $row['class'] . '::' . $row['function'],
            );
        }
        $tableHelper->setHeaders(array('#', 'File/Line', 'Method'));
        $tableHelper->setRows($rows);
        $tableHelper->render($output);
    }

    /**
     * @param OutputInterface $output
     * @param Exception $e
     */
    protected function printFile(OutputInterface $output, Exception $e)
    {
        if (preg_match('/Error\sin\sfile\:\s"(.+)\"\s-/', $e->getMessage(), $matches)) {
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $lines = \file($matches[1]);
            $rows = array();
            $i = 0;
            foreach ($lines as $line) {
                $rows[] = array(++$i, rtrim($line));
            }
            $tableHelper->setHeaders(array('Line', 'Code'));
            $tableHelper->setRows($rows);
            $tableHelper->render($output);
        }
    }

    private function flushCache()
    {
        /**
         * Get events before cache flush command is called.
         */
        $reflectionApp = new \ReflectionObject(\Mage::app());
        $appEventReflectionProperty = $reflectionApp->getProperty('_events');
        $appEventReflectionProperty->setAccessible(true);
        $eventsBeforeCacheFlush = $appEventReflectionProperty->getValue(\Mage::app());

        $application = $this->getApplication();
        $saved = $application->setAutoExit(false);
        $application->run(new StringInput('cache:flush'), new NullOutput());
        $application->setAutoExit($saved);

        /**
         * Restore initially loaded events which was reset during setup script run
         */
        $appEventReflectionProperty->setValue(\Mage::app(), $eventsBeforeCacheFlush);
    }
}
