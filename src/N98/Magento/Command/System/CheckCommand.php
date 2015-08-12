<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Unicode\Charset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCommand
 *
 * @package N98\Magento\Command\System
 */
class CheckCommand extends AbstractMagentoCommand
{
    const UNICODE_CHECKMARK_CHAR = 10004;
    const UNICODE_CROSS_CHAR = 10006;

    /**
     * Command config
     *
     * @var array
     */
    protected $_config;

    protected function configure()
    {
        $this
            ->setName('sys:check')
            ->setDescription('Checks Magento System')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;

        $help = <<<HELP
- Checks missing files and folders
- Security
- PHP Extensions (Required and Bytecode Cache)
- MySQL InnoDB Engine
HELP;
        $this->setHelp($help);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_config = $this->getCommandConfig();
        $this->detectMagento($output);
        if ($this->initMagento()) {

            $results = new ResultCollection();
            foreach ($this->_config['checks'] as $checkGroup => $checkGroupClasses) {
                $results->setResultGroup($checkGroup);
                foreach ($checkGroupClasses as $checkGroupClass) {
                    $this->_invokeCheckClass($results, $checkGroupClass);
                }
            }

            if ($input->getOption('format')) {
                $this->_printTable($input, $output, $results);
            } else {
                $this->_printResults($output, $results);
            }
        }
    }

    /**
     * @param ResultCollection $results
     * @param string           $checkGroupClass name
     */
    protected function _invokeCheckClass(ResultCollection $results, $checkGroupClass)
    {
        $check = $this->_createCheck($checkGroupClass);

        switch (true) {
            case $check instanceof Check\SimpleCheck:
                $check->check($results);
                break;

            case $check instanceof Check\StoreCheck:
                if (!$stores = \Mage::app()->getStores()) {
                    $this->_markCheckWarning($results, 'stores', $checkGroupClass);
                }
                foreach ($stores as $store) {
                    $check->check($results, $store);
                }
                break;

            case $check instanceof Check\WebsiteCheck:
                if (!$websites = \Mage::app()->getWebsites()) {
                    $this->_markCheckWarning($results, 'websites', $checkGroupClass);
                }
                foreach ($websites as $website) {
                    $check->check($results, $website);
                }
                break;

            default:
                throw new \LogicException(
                    sprintf('Unhandled check-class "%s"', $checkGroupClass)
                );
        }
    }

    /**
     * @param OutputInterface  $output
     * @param ResultCollection $results
     */
    protected function _printResults(OutputInterface $output, ResultCollection $results)
    {
        $lastResultGroup = null;
        foreach ($results as $result) {
            if ($result->getResultGroup() != $lastResultGroup) {
                $this->writeSection($output, str_pad(strtoupper($result->getResultGroup()), 60, ' ', STR_PAD_BOTH));
            }
            if ($result->getMessage()) {
                switch ($result->getStatus()) {
                    case Result::STATUS_WARNING:
                    case Result::STATUS_ERROR:
                        $output->write('<error>' . \N98\Util\Unicode\Charset::convertInteger(Charset::UNICODE_CROSS_CHAR) . '</error> ');
                        break;

                    case Result::STATUS_OK:
                    default:
                        $output->write('<info>' . \N98\Util\Unicode\Charset::convertInteger(Charset::UNICODE_CHECKMARK_CHAR) . '</info> ');
                        break;
                }
                $output->writeln($result->getMessage());
            }

            $lastResultGroup = $result->getResultGroup();
        }
    }

    /**
     * @param InputInterface   $input
     * @param OutputInterface  $output
     * @param ResultCollection $results
     */
    protected function _printTable(InputInterface $input, OutputInterface $output, ResultCollection $results)
    {
        $table = array();
        foreach ($results as $result) {
            /* @var $result Result */
            $table[] = array(
                $result->getResultGroup(),
                strip_tags($result->getMessage()),
                $result->getStatus()
            );
        }

        $this->getHelper('table')
            ->setHeaders(array('Group', 'Message', 'Result'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

    /**
     * @param string $checkGroupClass
     *
     * @return object
     */
    private function _createCheck($checkGroupClass)
    {
        $check = new $checkGroupClass();

        if ($check instanceof CommandAware) {
            $check->setCommand($this);
        }
        if ($check instanceof CommandConfigAware) {
            $check->setCommandConfig($this->_config);

            return $check;
        }

        return $check;
    }

    /**
     * @param ResultCollection $results
     * @param string           $context
     * @param string           $checkGroupClass
     */
    private function _markCheckWarning(ResultCollection $results, $context, $checkGroupClass)
    {
        $result = $results->createResult();
        $result->setMessage('<error>No ' . $context . ' configured to run store check:</error> <comment>' . basename($checkGroupClass) . '</comment>');
        $result->setStatus($result::STATUS_WARNING);
        $results->addResult($result);
    }
}
