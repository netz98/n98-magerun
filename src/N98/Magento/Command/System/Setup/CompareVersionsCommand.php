<?php

namespace N98\Magento\Command\System\Setup;

use N98\JUnitXml\Document as JUnitXmlDocument;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareVersionsCommand extends AbstractMagentoCommand
{
    protected function configure()
    {
        $this
            ->setName('sys:setup:compare-versions')
            ->addOption('ignore-data', null, InputOption::VALUE_NONE, 'Ignore data updates')
            ->addOption('log-junit', null, InputOption::VALUE_REQUIRED, 'Log output to a JUnit xml file.')
            ->addOption(
                'errors-only',
                null,
                InputOption::VALUE_NONE,
                'Only display Setup resources where Status equals Error.'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->setDescription('Compare module version with core_resource table.');
        $help = <<<HELP
Compares module version with saved setup version in `core_resource` table and displays version mismatch.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        $time = microtime(true);
        $modules = \Mage::getConfig()->getNode('modules');
        $resourceModel = $this->_getResourceSingleton('core/resource', 'Mage_Core_Model_Resource_Resource');
        $setups = \Mage::getConfig()->getNode('global/resources')->children();
        $ignoreDataUpdate = $input->getOption('ignore-data');

        $headers = array('Setup', 'Module', 'DB', 'Data', 'Status');
        if ($ignoreDataUpdate) {
            unset($headers[array_search('Data', $headers)]);
        }

        $hasStatusErrors = false;

        $errorCounter = 0;
        $table = array();
        foreach ($setups as $setupName => $setup) {
            $moduleName = (string) $setup->setup->module;
            $moduleVersion = (string) $modules->{$moduleName}->version;
            $dbVersion = (string) $resourceModel->getDbVersion($setupName);
            if (!$ignoreDataUpdate) {
                $dataVersion = (string) $resourceModel->getDataVersion($setupName);
            }
            $ok = $dbVersion == $moduleVersion;
            if ($ok && !$ignoreDataUpdate) {
                $ok = $dataVersion == $moduleVersion;
            }
            if (!$ok) {
                $errorCounter++;
            }

            $row = array(
                'Setup'     => $setupName,
                'Module'    => $moduleVersion,
                'DB'        => $dbVersion,
            );

            if (!$ignoreDataUpdate) {
                $row['Data-Version'] = $dataVersion;
            }
            $row['Status'] = $ok ? 'OK' : 'Error';

            if (!$ok) {
                $hasStatusErrors = true;
            }

            $table[] = $row;
        }

        if ($input->getOption('errors-only')) {
            $table = array_filter($table, function ($row) {
                return ($row['Status'] === 'Error');
            });
        }

        //if there is no output format
        //highlight the status
        //and show error'd rows at bottom
        if (!$input->getOption('format')) {
            usort($table, function ($a, $b) {
                if ($a['Status'] !== 'OK' && $b['Status'] === 'OK') {
                    return 1;
                }
                if ($a['Status'] === 'OK' && $b['Status'] !== 'OK') {
                    return -1;
                }
                return strcmp($a['Setup'], $b['Setup']);
            });

            array_walk($table, function (&$row) {
                $status = $row['Status'];
                $availableStatus = array('OK' => 'info', 'Error' => 'error');
                $statusString = sprintf(
                    '<%s>%s</%s>',
                    $availableStatus[$status],
                    $status,
                    $availableStatus[$status]
                );
                $row['Status'] = $statusString;
            });
        }

        if ($input->getOption('log-junit')) {
            $this->logJUnit($table, $input->getOption('log-junit'), microtime($time) - $time);
        } else {
            /* @var $tableHelper TableHelper */
            $tableHelper = $this->getHelper('table');
            $tableHelper
                ->setHeaders($headers)
                ->renderByFormat($output, $table, $input->getOption('format'));

            //if no output format specified - output summary line
            if (!$input->getOption('format')) {
                if ($errorCounter > 0) {
                    $this->writeSection(
                        $output,
                        sprintf(
                            '%s error%s %s found!',
                            $errorCounter,
                            $errorCounter === 1 ? '' : 's',
                            $errorCounter === 1 ? 'was' : 'were'
                        ),
                        'error'
                    );
                } else {
                    $this->writeSection($output, 'No setup problems were found.', 'info');
                }
            }
        }

        if ($hasStatusErrors) {
            //Return a non-zero status to indicate there is an error in the setup scripts.
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param array $data
     * @param string $filename
     * @param float $duration
     */
    protected function logJUnit(array $data, $filename, $duration)
    {
        $document = new JUnitXmlDocument();
        $suite = $document->addTestSuite();
        $suite->setName('n98-magerun: ' . $this->getName());
        $suite->setTimestamp(new \DateTime());
        $suite->setTime($duration);

        $testCase = $suite->addTestCase();
        $testCase->setName('Magento Setup Version Test');
        $testCase->setClassname('CompareVersionsCommand');
        if (count($data) > 0) {
            foreach ($data as $moduleSetup) {
                if (stristr($moduleSetup['Status'], 'error')) {
                    $testCase->addFailure(
                        sprintf(
                            'Setup Script Error: [Setup %s]',
                            $moduleSetup['Setup']
                        ),
                        'MagentoSetupScriptVersionException'
                    );
                }
            }
        }

        $document->save($filename);
    }
}
