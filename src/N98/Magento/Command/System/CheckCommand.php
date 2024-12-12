<?php

declare(strict_types=1);

namespace N98\Magento\Command\System;

use LogicException;
use Mage;
use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Magento\Command\System\Check\SimpleCheck;
use N98\Magento\Command\System\Check\StoreCheck;
use N98\Magento\Command\System\Check\WebsiteCheck;
use N98\Util\Unicode\Charset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * System check command
 *
 * @package N98\Magento\Command\System
 */
class CheckCommand extends AbstractMagentoCommand
{
    /**
     * Command config
     */
    protected array $config;

    protected function configure(): void
    {
        $this
            ->setName('sys:check')
            ->setDescription('Checks Magento System')
            ->addFormatOption();
    }

    public function getHelp(): string
    {
        return <<<HELP
- Checks missing files and folders
- Security
- PHP Extensions (Required and Bytecode Cache)
- MySQL InnoDB Engine
HELP;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
        }

        $this->config = $this->getCommandConfig();

        $resultCollection = new ResultCollection();

        foreach ($this->config['checks'] as $checkGroup => $checkGroupClasses) {
            $resultCollection->setResultGroup($checkGroup);
            foreach ($checkGroupClasses as $checkGroupClass) {
                $this->_invokeCheckClass($resultCollection, $checkGroupClass);
            }
        }

        if ($input->getOption('format')) {
            $this->_printTable($input, $output, $resultCollection);
        } else {
            $this->_printResults($output, $resultCollection);
        }

        return Command::SUCCESS;
    }

    protected function _invokeCheckClass(ResultCollection $resultCollection, string $checkGroupClass): void
    {
        $check = $this->_createCheck($checkGroupClass);

        switch (true) {
            case $check instanceof SimpleCheck:
                $check->check($resultCollection);
                break;

            case $check instanceof StoreCheck:
                $this->checkStores($resultCollection, $checkGroupClass, $check);
                break;

            case $check instanceof WebsiteCheck:
                $this->checkWebsites($resultCollection, $checkGroupClass, $check);
                break;

            default:
                throw new LogicException(
                    sprintf('Unhandled check-class "%s"', $checkGroupClass),
                );
        }
    }

    protected function _printResults(OutputInterface $output, ResultCollection $resultCollection): void
    {
        $lastResultGroup = null;
        foreach ($resultCollection as $result) {
            if ($result->getResultGroup() != $lastResultGroup) {
                $this->writeSection($output, str_pad(strtoupper($result->getResultGroup()), 60, ' ', STR_PAD_BOTH));
            }

            if ($result->getMessage()) {
                switch ($result->getStatus()) {
                    case Result::STATUS_WARNING:
                    case Result::STATUS_ERROR:
                        $output->write('<error>' . Charset::convertInteger(Charset::UNICODE_CROSS_CHAR) . '</error> ');
                        break;

                    case Result::STATUS_OK:
                    default:
                        $output->write(
                            '<info>' . Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR) . '</info> ',
                        );
                        break;
                }

                $output->writeln($result->getMessage());
            }

            $lastResultGroup = $result->getResultGroup();
        }
    }

    protected function _printTable(InputInterface $input, OutputInterface $output, ResultCollection $resultCollection): void
    {
        $table = [];
        foreach ($resultCollection as $result) {
            /** @var Result $result */
            $table[] = [
                $result->getResultGroup(),
                strip_tags($result->getMessage()),
                $result->getStatus(),
            ];
        }

        $tableHelper = $this->getTableHelper();
        $tableHelper
            ->setHeaders(['Group', 'Message', 'Result'])
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @return object
     */
    private function _createCheck(string $checkGroupClass)
    {
        $check = new $checkGroupClass();

        if ($check instanceof CommandAware) {
            $check->setCommand($this);
        }

        if ($check instanceof CommandConfigAware) {
            $check->setCommandConfig($this->config);
            return $check;
        }

        return $check;
    }

    private function _markCheckWarning(ResultCollection $resultCollection, string $context, string $checkGroupClass): void
    {
        $result = $resultCollection->createResult();
        $result->setMessage(
            '<error>No ' . $context . ' configured to run store check:</error> <comment>' . basename($checkGroupClass) .
            '</comment>',
        );
        $result->setStatus($result::STATUS_WARNING);

        $resultCollection->addResult($result);
    }

    private function checkStores(ResultCollection $resultCollection, string $checkGroupClass, StoreCheck $storeCheck): void
    {
        if (!$stores = Mage::app()->getStores()) {
            $this->_markCheckWarning($resultCollection, 'stores', $checkGroupClass);
        }

        foreach ($stores as $store) {
            $storeCheck->check($resultCollection, $store);
        }
    }

    private function checkWebsites(ResultCollection $resultCollection, string $checkGroupClass, WebsiteCheck $websiteCheck): void
    {
        if (!$websites = Mage::app()->getWebsites()) {
            $this->_markCheckWarning($resultCollection, 'websites', $checkGroupClass);
        }

        foreach ($websites as $website) {
            $websiteCheck->check($resultCollection, $website);
        }
    }
}
