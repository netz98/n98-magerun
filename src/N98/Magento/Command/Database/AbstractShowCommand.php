<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Filesystem;

abstract class AbstractShowCommand extends AbstractDatabaseCommand
{
    protected $showMethod = 'getGlobalVariables';

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $_input = null;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $_output = null;

    /**
     * @var array
     */
    protected $_importantVars = array();

    /**
     * Key = variable name => value method name in this class
     *
     * @var array
     */
    protected $_specialFormat = array();

    /**
     * Contains all variables
     *
     * @var array
     */
    protected $_allVariables = array();

    protected function configure()
    {
        $this
            ->addArgument(
                'search',
                InputArgument::OPTIONAL,
                'Only output variables of specified name. The wildcard % is supported!'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->addOption(
                'rounding',
                null,
                InputOption::VALUE_OPTIONAL,
                'Amount of decimals to display. If -1 then disabled',
                0
            )
            ->addOption(
                'no-description',
                null,
                InputOption::VALUE_NONE,
                'Disable description'
            );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input  = $input;
        $this->_output = $output;
        $this->initVariables($this->_input->getArgument('search'));
        $outputVars = $this->_allVariables;
        if (null === $this->_input->getArgument('search')) {
            $outputVars = array_intersect_key($this->_allVariables, $this->_importantVars);
        }

        $outputVars = $this->formatVariables($outputVars);
        reset($this->_importantVars);
        $hasDescription = isset($this->_importantVars[key($this->_importantVars)]['desc']) &&
            false === $this->_input->getOption('no-description');
        $header         = array('Variable Name', 'Value');
        if (true === $hasDescription) {
            $header[] = 'Description';
        }

        $this->renderTable($header, $this->generateRows($outputVars, $hasDescription));
    }

    /**
     * @param array $outputVars
     * @param bool  $hasDescription
     *
     * @return array
     */
    protected function generateRows(array $outputVars, $hasDescription)
    {
        $rows = array();
        $i    = 0;
        foreach ($outputVars as $variableName => $variableValue) {
            $rows[$i] = array($variableName, $variableValue);
            if (
                true === $hasDescription &&
                isset($this->_importantVars[$variableName], $this->_importantVars[$variableName]['desc'])
            ) {
                $rows[$i][] = $this->formatDesc($this->_importantVars[$variableName]['desc']);
            }
            $i++;
        }
        // when searching no every variable has a description so fill the missing ones with blanks
        if (false === $hasDescription) {
            return $rows;
        }
        foreach ($rows as $k => $r) {
            if (2 === count($r)) {
                $rows[$k] = $this->getVariableDescription($r);
            }
        }
        return $rows;
    }

    /**
     * Extend or modify this method to add descriptions to other variables
     *
     * @param array $row
     *
     * @return array
     */
    protected function getVariableDescription(array $row)
    {
        $row[] = '';
        return $row;
    }

    /**
     * Formats the description
     *
     * @param string $desc
     *
     * @return string
     */
    protected function formatDesc($desc)
    {
        $desc = preg_replace('~\s+~', ' ', $desc);
        return wordwrap($desc);
    }

    /**
     * @param array $header
     * @param array $rows
     */
    protected function renderTable(array $header, array $rows)
    {
        /** @var \N98\Util\Console\Helper\TableHelper $t */
        $t = $this->getHelper('table');
        $t->setHeaders($header)
            ->renderByFormat($this->_output, $rows, $this->_input->getOption('format'));
    }

    /**
     * @param string|null $variable
     */
    protected function initVariables($variable = null)
    {
        /** @var \N98\Util\Console\Helper\DatabaseHelper $database */
        $database            = $this->getHelper('database');
        $this->_allVariables = $database->{$this->showMethod}($variable);
    }

    /**
     * @param array $vars
     *
     * @return array
     */
    protected function formatVariables(array $vars)
    {
        $rounding = (int) $this->_input->getOption('rounding');
        if ($rounding > -1) {
            foreach ($vars as $k => &$v) {
                if (true === $this->allowRounding($k)) {
                    $v = Filesystem::humanFileSize($v, $rounding);
                }
                if (isset($this->_specialFormat[$k])) {
                    $v = $this->{$this->_specialFormat[$k]}($v);
                }
            }
            unset($v);
        }
        $maxWidth = $this->getMaxValueWidth($vars);
        // align=right
        foreach ($vars as &$v) {
            $v = str_pad($v, $maxWidth, ' ', STR_PAD_LEFT);
        }
        return $vars;
    }

    /**
     * @param array $vars
     *
     * @return int
     */
    protected function getMaxValueWidth(array $vars)
    {
        $maxWidth = 0;
        foreach ($vars as $v) {
            $l = strlen($v);
            if ($l > $maxWidth) {
                $maxWidth = $l;
            }
        }
        return $maxWidth;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    abstract protected function allowRounding($name);
}
