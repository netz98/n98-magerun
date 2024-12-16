<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Setup;

use Error;
use Mage;
use Mage_Core_Model_Config_Element;
use Mage_Core_Model_Resource_Resource;
use N98\JUnitXml\Document as JUnitXmlDocument;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compare setup versions command
 *
 * @package N98\Magento\Command\System\Setup
 */
class CompareVersionsCommand extends AbstractMagentoCommand
{
    protected function configure(): void
    {
        $this
            ->setName('sys:setup:compare-versions')
            ->addOption('ignore-data', null, InputOption::VALUE_NONE, 'Ignore data updates')
            ->addOption('log-junit', null, InputOption::VALUE_REQUIRED, 'Log output to a JUnit xml file.')
            ->addOption(
                'errors-only',
                null,
                InputOption::VALUE_NONE,
                'Only display Setup resources where Status equals Error.',
            )
            ->addFormatOption()
            ->setDescription('Compare module version with core_resource table.');
    }

    public function getHelp(): string
    {
        return <<<HELP
Compares module version with saved setup version in `core_resource` table and displays version mismatch.
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $config = Mage::getConfig();
        if (!$config) {
            return Command::INVALID;
        }

        $time = microtime(true);
        $modules = $config->getNode('modules');
        /** @var Mage_Core_Model_Resource_Resource $mageCoreModelAbstract */
        $mageCoreModelAbstract = Mage::getModel('core/resource_resource');
        /** @var Mage_Core_Model_Config_Element $node */
        $node = $config->getNode('global/resources');
        $setups = $node->children();
        $ignoreDataUpdate = $input->getOption('ignore-data');

        $headers = ['Setup', 'Module', 'DB', 'Data', 'Status'];
        if ($ignoreDataUpdate) {
            unset($headers[array_search('Data', $headers, true)]);
        }

        $hasStatusErrors = false;

        $dataVersion = null;
        $errorCounter = 0;
        $table = [];
        foreach ($setups as $setupName => $setup) {
            $moduleName = (string) $setup->setup->module;
            $moduleVersion = (string) $modules->{$moduleName}->version;
            $dbVersion = (string) $mageCoreModelAbstract->getDbVersion($setupName);
            if (!$ignoreDataUpdate) {
                $dataVersion = (string) $mageCoreModelAbstract->getDataVersion($setupName);
            }

            $ok = $dbVersion === $moduleVersion;
            if ($ok && !$ignoreDataUpdate) {
                $ok = $dataVersion == $moduleVersion;
            }

            if (!$ok) {
                ++$errorCounter;
            }

            $row = ['Setup'     => $setupName, 'Module'    => $moduleVersion, 'DB'        => $dbVersion];

            if (!$ignoreDataUpdate) {
                $row['Data-Version'] = $dataVersion;
            }

            $row['Status'] = $ok ? 'OK' : Error::class;

            if (!$ok) {
                $hasStatusErrors = true;
            }

            $table[] = $row;
        }

        if ($input->getOption('errors-only')) {
            $table = array_filter($table, function ($row) {
                return ($row['Status'] === Error::class);
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

            array_walk($table, function (&$row): void {
                $status = $row['Status'];
                $availableStatus = ['OK' => 'info', Error::class => 'error'];
                $statusString = sprintf(
                    '<%s>%s</%s>',
                    $availableStatus[$status],
                    $status,
                    $availableStatus[$status],
                );
                $row['Status'] = $statusString;
            });
        }

        if ($input->getOption('log-junit')) {
            $this->logJUnit($table, $input->getOption('log-junit'), microtime(true) - $time);
        } else {
            $tableHelper = $this->getTableHelper();
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
                            $errorCounter === 1 ? 'was' : 'were',
                        ),
                        'error',
                    );
                } else {
                    $this->writeSection($output, 'No setup problems were found.', 'info');
                }
            }
        }

        if ($hasStatusErrors) {
            //Return a non-zero status to indicate there is an error in the setup scripts.
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function logJUnit(array $data, string $filename, float $duration): void
    {
        $document = new JUnitXmlDocument();
        $testSuiteElement = $document->addTestSuite();
        $testSuiteElement->setName('n98-magerun: ' . $this->getName());
        $testSuiteElement->setTimestamp(\Carbon\Carbon::now());
        $testSuiteElement->setTime($duration);

        $testCaseElement = $testSuiteElement->addTestCase();
        $testCaseElement->setName('Magento Setup Version Test');
        $testCaseElement->setClassname('CompareVersionsCommand');
        foreach ($data as $moduleSetup) {
            if (stristr($moduleSetup['Status'], 'error')) {
                $testCaseElement->addFailure(
                    sprintf(
                        'Setup Script Error: [Setup %s]',
                        $moduleSetup['Setup'],
                    ),
                    'MagentoSetupScriptVersionException',
                );
            }
        }

        $document->save($filename);
    }
}
