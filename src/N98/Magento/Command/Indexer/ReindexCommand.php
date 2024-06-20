<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use Exception;
use InvalidArgumentException;
use Mage_Index_Model_Process;
use N98\Util\BinaryString;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Re-index command
 *
 * @package N98\Magento\Command\Indexer
 */
class ReindexCommand extends AbstractIndexerCommand
{
    public const COMMAND_ARGUMENT_INDEX_CODE = 'index_code';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'index:reindex';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'Reindex a index by code.';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::COMMAND_ARGUMENT_INDEX_CODE,
                InputArgument::OPTIONAL,
                'Code of indexer.'
            )
        ;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return <<<HELP
Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

   $ n98-magerun.phar index:reindex [code]


Since 1.75.0 it's possible to run mutiple indexers by seperating code with a comma.

i.e.

   $ n98-magerun.phar index:reindex catalog_product_attribute,tag_summary

If no index is provided as argument you can select indexers from menu by "number" like "1,3" for first and third
indexer.
HELP;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        $this->initMagento();

        $this->writeSection($output, 'Reindex');
        $this->disableObservers();
        $indexCode = $input->getArgument(self::COMMAND_ARGUMENT_INDEX_CODE);
        if ($indexCode === null) {
            /** @var array<int, string> $indexCodes */
            $indexCodes = $this->askForIndexCodes($input, $output);
        } else {
            // take cli argument
            $indexCodes = BinaryString::trimExplodeEmpty(',', $indexCode);
        }

        $processes = $this->getProcessesByIndexCodes($indexCodes);
        if (!$this->executeProcesses($output, $processes)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string> $indexCodes
     * @return array<int, Mage_Index_Model_Process>
     */
    private function getProcessesByIndexCodes(array $indexCodes): array
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
     * @return mixed
     * @throws Exception
     */
    private function askForIndexCodes(InputInterface $input, OutputInterface $output)
    {
        $indexerList = $this->getIndexerList();
        $choices = [];
        foreach ($indexerList as $indexer) {
            $choices[] = sprintf(
                '%-40s <info>(last runtime: %s)</info>',
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
                if (!isset($indexerList[$typeInput])) {
                    throw new InvalidArgumentException('Invalid indexer');
                }

                $returnCodes[] = $indexerList[$typeInput]['code'];
            }

            return $returnCodes;
        };

        $dialog = $this->getQuestionHelper();
        $question = new ChoiceQuestion(
            '<question>Please select a indexer:</question> ',
            $choices
        );
        $question->setValidator($validator);

        return $dialog->ask($input, $output, $question);
    }
}
