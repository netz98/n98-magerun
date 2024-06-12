<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\AbstractMagentoCommandFormatInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ReportCommand extends AbstractCacheCommand implements AbstractMagentoCommandFormatInterface
{
    public const COMMAND_OPTION_FILTER_ID = 'filter-id';

    public const COMMAND_OPTION_FILTER_TAG = 'filter-tag';

    public const COMMAND_OPTION_TAGS = 'tags';

    public const COMMAND_OPTION_MTIME = 'mtime';

    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultName = 'cache:report';

    /**
     * @var string
     * @deprecated with symfony 6.1
     * @see AsCommand
     */
    protected static $defaultDescription = 'View inside the cache.';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_TAGS,
                't', InputOption::VALUE_NONE,
                'Output tags'
            )
            ->addOption(
                self::COMMAND_OPTION_MTIME,
                'm',
                InputOption::VALUE_NONE,
                'Output last modification time'
            )
            ->addOption(
                self::COMMAND_OPTION_FILTER_ID,
                '',
                InputOption::VALUE_OPTIONAL,
                'Filter output by ID (substring)'
            )
            ->addOption(
                self::COMMAND_OPTION_FILTER_TAG,
                '',
                InputOption::VALUE_OPTIONAL,
                'Filter output by TAG (separate multiple tags by comma)'
            )
            ->addOption(
                self::COMMAND_OPTION_FPC,
                null,
                InputOption::VALUE_NONE,
                'Use full page cache instead of core cache (Enterprise only!)'
            )
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @return array<int|string, array<string, string>>
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
     */
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $cacheInstance = $this->getCacheInstance($input);

            $filterTag = $input->getOption(self::COMMAND_OPTION_FILTER_TAG);
            if ($filterTag !== null) {
                $cacheIds = $cacheInstance->getIdsMatchingAnyTags([$filterTag]);
            } else {
                $cacheIds = $cacheInstance->getIds();
            }

            $filterId = $input->getOption(self::COMMAND_OPTION_FILTER_ID);
            if ($filterId !== null) {
                $cacheIds = array_filter($cacheIds, function ($cacheId) use ($filterId) {
                    return stristr($cacheId, (string)$filterId);
                });
            }

            foreach ($cacheIds as $cacheId) {
                $metaData = $cacheInstance->getMetadatas($cacheId);

                $row = [
                    'ID' => $cacheId,
                    'EXPIRE' => date(self::DATE_FORMAT, $metaData['expire'])
                ];

                if ($input->getOption(self::COMMAND_OPTION_MTIME)) {
                    $row['MTIME'] = date(self::DATE_FORMAT, $metaData['mtime']);
                }

                if ($input->getOption(self::COMMAND_OPTION_TAGS)) {
                    $row['TAGS'] = implode(',', $metaData['tags']);
                }

                $this->data[] = $row;
            }
        }

        return $this->data;
    }
}
