<?php

declare(strict_types=1);

namespace N98\Magento\Command\Indexer;

use InvalidArgumentException;
use N98\Util\BinaryString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Reindex command
 *
 * @package N98\Magento\Command\Indexer
 */
class ReindexCommand extends AbstractIndexerCommand
{
    protected function configure(): void
    {
        $this
            ->setName('index:reindex')
            ->addArgument('index_code', InputArgument::OPTIONAL, 'Code of indexer.')
            ->setDescription('Reindex a magento index by code');
    }

    public function getHelp(): string
    {
        return <<<HELP
Index by indexer code. Code is optional. If you don't specify a code you can pick a indexer from a list.

   $ n98-magerun.phar index:reindex [code]


Since 1.75.0 it's possible to run multiple indexers by separating code with a comma.

i.e.

   $ n98-magerun.phar index:reindex catalog_product_attribute,tag_summary

If no index is provided as argument you can select indexers from menu by "number" like "1,3" for first and third
indexer.
HELP;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return Command::INVALID;
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
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getProcessesByIndexCodes(array $indexCodes): array
    {
        $processes = [];
        foreach ($indexCodes as $indexCode) {
            $process = $this->getIndexerModel()->getProcessByCode($indexCode);
            if (!$process) {
                throw new InvalidArgumentException(sprintf('Indexer "%s" was not found!', $indexCode));
            }

            $processes[] = $process;
        }

        return $processes;
    }

    private function askForIndexCodes(InputInterface $input, OutputInterface $output): array
    {
        $indexerList = $this->getIndexerList();
        $choices = [];
        foreach ($indexerList as $indexer) {
            $choices[] = sprintf(
                '%-40s <info>(last runtime: %s)</info>',
                $indexer['code'],
                $indexer['last_runtime'],
            );
        }

        $validator = function ($typeInput) use ($indexerList) {
            $typeInputs = strstr($typeInput, ',') ? BinaryString::trimExplodeEmpty(',', $typeInput) : [$typeInput];

            $returnCodes = [];
            foreach ($typeInputs as $typeInput) {
                if (!isset($indexerList[$typeInput])) {
                    throw new InvalidArgumentException('Invalid indexer');
                }

                $returnCodes[] = $indexerList[$typeInput]['code'];
            }

            return $returnCodes;
        };

        $questionHelper = $this->getQuestionHelper();
        $choiceQuestion = new ChoiceQuestion(
            '<question>Please select a indexer:</question> ',
            $choices,
        );
        $choiceQuestion->setValidator($validator);

        return $questionHelper->ask($input, $output, $choiceQuestion);
    }
}
