<?php

namespace N98\Magento\Command\Database;

use Description;
use N98\Util\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractShowCommand
 *
 * @package N98\Magento\Command\Database
 */
abstract class AbstractShowCommand extends AbstractDatabaseCommand
{
    protected $showMethod = 'getGlobalVariables';

    /**
     * @var InputInterface
     */
    protected $_input = null;

    /**
     * @var OutputInterface
     */
    protected $_output = null;

    /**
     * @var array
     */
    protected $_importantVars = [];

    /**
     * Key = variable name => value method name in this class
     *
     * @var array
     */
    protected $_specialFormat = [];

    /**
     * Contains all variables
     *
     * @var array
     */
    protected $_allVariables = [];

    protected function configure()
    {
        $this
            ->addArgument(
                'search',
                InputArgument::OPTIONAL,
                'Only output variables of specified name. The wildcard % is supported!'
            )
            ->addFormatOption()
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->initVariables($this->_input->getArgument('search'));
        $outputVars = $this->_allVariables;
        if (null === $this->_input->getArgument('search')) {
            $outputVars = array_intersect_key($this->_allVariables, $this->_importantVars);
        }

        $outputVars = $this->formatVariables($outputVars);
        $hasDescription = isset($this->_importantVars[array_key_first($this->_importantVars)]['desc']) &&
            false === $this->_input->getOption('no-description');
        $header = ['Variable Name', 'Value'];
        if (true === $hasDescription) {
            $header[] = Description::class;
        }

        $this->renderTable($header, $this->generateRows($outputVars, $hasDescription));
        return 0;
    }

    /**
     * @param array $outputVars
     * @param bool  $hasDescription
     *
     * @return array
     */
    protected function generateRows(array $outputVars, $hasDescription)
    {
        $rows = [];
        $i = 0;
        foreach ($outputVars as $variableName => $variableValue) {
            $rows[$i] = [$variableName, $variableValue];
            if (true === $hasDescription &&
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
        $tableHelper = $this->getTableHelper();
        $tableHelper->setHeaders($header)
            ->renderByFormat($this->_output, $rows, $this->_input->getOption('format'));
    }

    /**
     * @param string|null $variable
     */
    protected function initVariables($variable = null)
    {
        $database = $this->getDatabaseHelper();
        $this->_allVariables = $database->{$this->showMethod}($variable);
    }

    /**
     * @param array $vars
     *
     * @return array
     */
    protected function formatVariables(array $vars)
    {
        $isStandardFormat = $this->_input->getOption('format') === null;
        $rounding = (int) $this->_input->getOption('rounding');
        if ($rounding > -1) {
            foreach ($vars as $k => &$v) {
                $v = trim($v);
                if (true === $this->allowRounding($k)) {
                    $v = Filesystem::humanFileSize($v, $rounding);
                }
                if (isset($this->_specialFormat[$k])) {
                    $formatter = $this->_specialFormat[$k];
                    if (is_string($formatter) && method_exists($this, $formatter)) {
                        $formatter = [$this, $formatter];
                    }
                    $v = call_user_func($formatter, $v);
                }
            }
            unset($v);
        }

        if ($isStandardFormat) {
            // align=right
            $maxWidth = $this->getMaxValueWidth($vars);
            foreach ($vars as &$v) {
                $v = str_pad($v, $maxWidth, ' ', STR_PAD_LEFT);
            }
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
