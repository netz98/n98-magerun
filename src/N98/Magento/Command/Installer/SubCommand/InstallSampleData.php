<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;
use N98\Util\Console\Helper\DatabaseHelper;
use N98\Util\Database;
use N98\Util\Exec;
use N98\Util\Filesystem;
use N98\Util\OperatingSystem;
use N98\Util\StringTyped;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use WpOrg\Requests\Requests;

/**
 * Class InstallSampleData
 * @package N98\Magento\Command\Installer\SubCommand
 */
class InstallSampleData extends AbstractSubCommand
{
    /**
     * @return void
     */
    public function execute()
    {
        if ($this->input->getOption('noDownload')) {
            return;
        }

        $questionHelper = $this->command->getHelper('question');

        $installSampleData = ($this->input->getOption('installSampleData') !== null)
            ? StringTyped::parseBoolOption($this->input->getOption('installSampleData'))
            : $questionHelper->ask(
                $this->input,
                $this->output,
                new ConfirmationQuestion('<question>Install sample data?</question> <comment>[yes]</comment>: ', true)
            );

        if (!$installSampleData) {
            return;
        }

        $installationFolder = $this->config->getString('installationFolder');
        chdir($installationFolder);

        $flag = $this->getOptionalBooleanOption(
            'installSampleData',
            'Install sample data?',
            'no'
        );

        if (!$flag) {
            return;
        }

        $magentoPackage = $this->config['magentoPackage'];
        if (!isset($magentoPackage['extra'])) {
            return;
        }

        if (!isset($magentoPackage['extra']['sample-data'])) {
            return;
        }

        foreach ($this->commandConfig['demo-data-packages'] as $demoPackageData) {
            if ($demoPackageData['name'] === $magentoPackage['extra']['sample-data']) {
                $this->installSampleData($demoPackageData);
            }
        }
    }

    /**
     * @param array $demoPackageData
     * @return void
     */
    private function installSampleData(array $demoPackageData): void
    {
        $filesystem = new Filesystem();

        $this->output->writeln('<info>Installing sample data</info>');
        $this->output->writeln('<comment>This may take a while...</comment>');

        /**
         * Example config of a demo data package:
         *
         * - name: sample-data-1.9.2.4
         *   version: 1.9.2.4
         *   dist:
         *     url: https://github.com/Vinai/compressed-magento-sample-data/raw/master/compressed-magento-sample-data-1.9.2.4.tgz
         *     type: tar
         *     shasum: bb009ed09e1cf23d1aa43ca74a9a518bccb14545
         */
        // increase timeout to 3600 seconds
        $options = [
            'timeout' => 3600,
        ];
        $response = Requests::get($demoPackageData['dist']['url'], [], $options);
        if (!$response->success) {
            throw new \RuntimeException('Cannot download sample data file: ' . $response->status_code);
        }

        $sampleDataFileContent = $response->body;

        $expandedFolder = $this->extractFile($demoPackageData['dist']['type'], $sampleDataFileContent);

        if (is_dir($expandedFolder)) {
            $filesystem->recursiveCopy(
                $expandedFolder,
                $this->config['installationFolder']
            );
            $filesystem->recursiveRemoveDirectory($expandedFolder);
        }

        // Install sample data
        $sampleDataSqlFile = glob(
            $this->config['installationFolder'] . '/magento_*sample_data*sql'
        );

        /** @var DatabaseHelper $dbHelper */
        $dbHelper = $this->command->getHelper('database');

        if (isset($sampleDataSqlFile[0])) {
            $this->output->writeln('<info>Import sample data db data</info>');
            $exec = 'mysql ' . $dbHelper->getMysqlClientToolConnectionString() . ' < ' . $sampleDataSqlFile[0];

            Exec::run($exec, $commandOutput, $returnValue);

            if ($returnValue != 0) {
                $this->output->writeln('<error>' . $commandOutput . '</error>');
            }

            unlink($sampleDataSqlFile[0]);
        }

        if (is_dir($this->config['installationFolder'] . '/_temp_demo_data')) {
            $filesystem->recursiveRemoveDirectory($this->config['installationFolder'] . '/_temp_demo_data');
        }

        $this->output->writeln('<info>Sample data installed</info>');
    }

    /**
     * Extract file and return path to directory
     *
     * @param $type
     * @param string $sampleDataFileContent
     * @return string
     */
    private function extractFile($type, string $sampleDataFileContent): string
    {
        mkdir($this->config['installationFolder'] . '/_temp_demo_data');

        $sampleDataFile = $this->config['installationFolder'] . '/_temp_demo_data/_sample_data_file.' . $type;
        file_put_contents($sampleDataFile, $sampleDataFileContent);

        // extract sample data file by file extension
        switch ($type) {
            case 'tar':
                $this->extractTar($sampleDataFile);
                break;
            case 'zip':
                $this->extractZip($sampleDataFile);
                break;
            default:
                throw new \RuntimeException('Cannot extract sample data file: unknown file extension');
        }

        // remove sample data file
        unlink($sampleDataFile);

        $expandedFolder = $this->config['installationFolder'] . '/_temp_demo_data';
        // Check if expanded folder contains only one directory. If yes, use this as expanded folder
        $expandedFolderContent = scandir($expandedFolder);
        if (count($expandedFolderContent) === 3) {
            return $expandedFolder . '/' . $expandedFolderContent[2];
        }

        throw new \RuntimeException('Cannot extract sample data file: unknown file structure');
    }

    /**
     * @param string $sampleDataFile
     * @return void
     */
    private function extractTar(string $sampleDataFile): void
    {
        $process = new Process(
            ['tar', '-xzf', $sampleDataFile],
            $this->config['installationFolder'] . '/_temp_demo_data'
        );
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Cannot extract sample data file: ' . $process->getErrorOutput());
        }
    }

    /**
     * @param string $sampleDataFile
     * @return void
     */
    private function extractZip(string $sampleDataFile): void
    {
        $process = new Process(
            ['unzip', $sampleDataFile],
            $this->config['installationFolder'] . '/_temp_demo_data'
        );
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Cannot extract sample data file: ' . $process->getErrorOutput());
        }
    }
}
