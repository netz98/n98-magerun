<?php

namespace N98\Magento\Command\Indexer;

use InvalidArgumentException;
use Mage_Index_Model_Process;
use N98\Util\BinaryString;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class ReindexCommand extends AbstractIndexerCommand
{
    protected function configure()
    {
        $this
            ->setName('index:reindex')
            ->addArgument('index_code', InputArgument::OPTIONAL, 'Code of indexer.')
            ->setDescription('Reindex a magento index by code');

        $help = <<<HELP
Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

   $ n98-magerun.phar index:reindex [code]


Since 1.75.0 it's possible to run mutiple indexers by seperating code with a comma.

i.e.

   $ n98-magerun.phar index:reindex catalog_product_attribute,tag_summary

If no index is provided as argument you can select indexers from menu by "number" like "1,3" for first and third
indexer.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return 0;
        }

        $this->writeSection($output, 'Reindex');
        $this->disableObservers();
        $indexCode = $input->getArgument('index_code');
        if ($indexCode === null) {
            $indexCodes = $this->askForIndexCodes($input, $output);
        } else {
            // take cli argument
            $indexCodes = BinaryString::trimExplodeEmpty(',', $indexCode);
        }

        $processes = $this->getProcessesByIndexCodes($indexCodes);
        if (!$this->executeProcesses($output, $processes)) {
            return 1; // end with error
        }

        return 0;
    }

    /**
     * @param $indexCodes
     *
     * @return array
     */
    private function getProcessesByIndexCodes($indexCodes)
    {
        $processes = [];
        foreach ($indexCodes as $indexCode) {
            /* @var Mage_Index_Model_Process $process */
            $process = $this->getIndexerModel()->getProcessByCode($indexCode);
            if (!$process) {
                throw new InvalidArgumentException(sprintf('Indexer "%s" was not found!', $indexCode));
            }
            $processes[] = $process;
        }
        return $processes;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array
     */
    private function askForIndexCodes(InputInterface $input, OutputInterface $output)
    {
        $indexerList = $this->getIndexerList();
        $choices = [];
        foreach ($indexerList as $key => $indexer) {
            $choices[] = sprintf(
                "<comment>%-4s</comment> %-40s <info>(last runtime: %s)</info>\n",
                '[' . ($key + 1) . ']',
                $indexer['code'],
                $indexer['last_runtime']
            );
        }

        $validator = function ($typeInput) use ($indexerList) {
            if (strstr($typeInput, ',')) {
                $typeInputs = BinaryString::trimExplodeEmpty(',', $typeInput);
            } else {
                $typeInputs = [$typeInput];
            }

            $returnCodes = [];
            foreach ($typeInputs as $typeInput) {
                if (!isset($indexerList[$typeInput - 1])) {
                    throw new InvalidArgumentException('Invalid indexer');
                }

                $returnCodes[] = $indexerList[$typeInput - 1]['code'];
            }

            return $returnCodes;
        };

        /* @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<question>Please select a indexer:</question>',
            $choices
        );
        $question->setValidator($validator);

        return $dialog->ask($input, $output, $question);
    }
}
