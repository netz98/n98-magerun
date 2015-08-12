<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Magento\Command\CommandAware;
use N98\Magento\Command\CommandConfigAware;
use N98\Magento\Command\System\Check\Result;
use N98\Magento\Command\System\Check\ResultCollection;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param mixed $checkGroupClass
     * @internal param ResultCollection $resultCollection
     */
    protected function _invokeCheckClass(ResultCollection $results, $checkGroupClass)
    {
        $check = new $checkGroupClass();
        if ($check instanceof CommandAware) {
            $check->setCommand($this);
        }
        if ($check instanceof CommandConfigAware) {
            $check->setCommandConfig($this->_config);
        }

        if ($check instanceof Check\SimpleCheck) {
            $check->check($results);
        } elseif ($check instanceof Check\StoreCheck) {
            foreach (\Mage::app()->getStores() as $store) {
                $check->check($results, $store);
            }
        } elseif ($check instanceof Check\WebsiteCheck) {
            foreach (\Mage::app()->getWebsites() as $website) {
                $check->check($results, $website);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param Result $result
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
                        $output->write('<error>' . \N98\Util\Unicode\Charset::convertInteger(self::UNICODE_CROSS_CHAR) . '</error> ');
                        break;

                    default:
                    case Result::STATUS_OK:
                        $output->write('<info>' . \N98\Util\Unicode\Charset::convertInteger(self::UNICODE_CHECKMARK_CHAR) . '</info> ');
                        break;
                }
                $output->writeln($result->getMessage());
            }

            $lastResultGroup = $result->getResultGroup();
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Result $result
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
}
