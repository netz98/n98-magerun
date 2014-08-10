<?php

namespace N98\Magento\Command\System\Setup;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:run')
            ->setDescription('Runs all new setup scripts.');
        $help = <<<HELP
Runs all setup scripts (no need to call frontend).
This command is useful if you update your system with enabled maintenance mode.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface   $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setAutoExit(false);
        $this->detectMagento($output);
        if ($this->initMagento()) {
            try {
                /**
                 * Get events before cache flush command is called.
                 */
                $reflectionApp = new \ReflectionObject(\Mage::app());
                $appEventReflectionProperty = $reflectionApp->getProperty('_events');
                $appEventReflectionProperty->setAccessible(true);
                $eventsBeforeCacheFlush = $appEventReflectionProperty->getValue(\Mage::app());

                $this->getApplication()->run(new StringInput('cache:flush'), new NullOutput());

                /**
                 * Restore initially loaded events which was reset during setup script run
                 */
                $appEventReflectionProperty->setValue(\Mage::app(), $eventsBeforeCacheFlush);

                /**
                 * Put output in buffer. \Mage_Core_Model_Resource_Setup::_modifyResourceDb should print any error
                 * directly to stdout. Use execption which will be thrown to show error
                 */
                \ob_start();
                \Mage_Core_Model_Resource_Setup::applyAllUpdates();
                if (is_callable(array('\Mage_Core_Model_Resource_Setup', 'applyAllDataUpdates'))) {
                    \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
                }
                \ob_end_clean();
                $output->writeln('<info>done</info>');
            } catch (\Exception $e) {
                \ob_end_clean();
                $this->printException($output, $e);
                $this->printStackTrace($output, $e->getTrace());
                $this->printFile($output, $e);

                return 1; // exit with error status
            }
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param array           $trace
     *
     * @return void
     */
    protected function printStackTrace(OutputInterface $output, array $trace)
    {
        $rootFolder = $this->getApplication()->getMagentoRootFolder();
        $trace = array_filter($trace, function(&$row) use ($rootFolder) {
            if (!strstr($row['file'], $rootFolder)) {
                return false;
            }

            $row['file'] = ltrim(str_replace($rootFolder, '', $row['file']), '/');

            return $row;
        });

        $table = $this->getHelper('table');
        $rows = array();
        $i = 1;
        foreach ($trace as $row) {
            $rows[] = array(
                $i++,
                $row['file'] . ':' . $row['line'],
                $row['class'] . '::' . $row['function']
            );
        }
        $table->setHeaders(array('#', 'File/Line', 'Method'));
        $table->setRows($rows);

        $table->render($output);
    }

    /**
     * @param OutputInterface $output
     * @param                 $e
     */
    protected function printException(OutputInterface $output, $e)
    {
        $output->writeln('<error>' . $e->getMessage() . '</error>');
    }

    /**
     * @param OutputInterface $output
     * @param                 $e
     */
    protected function printFile(OutputInterface $output, $e)
    {
        if (preg_match('/Error\sin\sfile\:\s"(.+)\"\s-/', $e->getMessage(), $matches)) {
            $table = $this->getHelper('table');
            $lines = \file($matches[1]);
            $rows = array();
            $i = 0;
            foreach ($lines as $line) {
                $rows[] = array(++$i, rtrim($line));
            }
            $table->setHeaders(array('Line', 'Code'));
            $table->setRows($rows);
            $table->render($output);
        }
    }
}
