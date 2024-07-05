<?php

declare(strict_types=1);

namespace N98\Magento\Command\Cache;

use N98\Magento\Command\CommandDataInterface;
use N98\Magento\Methods\MageBase as Mage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Report cache command
 *
 * @package N98\Magento\Command\Cache
 */
class ReportCommand extends AbstractCacheCommand implements CommandDataInterface
{
    public const COMMAND_OPTION_FILTER_ID = 'filter-id';

    public const COMMAND_OPTION_FILTER_TAG = 'filter-tag';

    public const COMMAND_OPTION_TAGS = 'tags';

    public const COMMAND_OPTION_MTIME = 'mtime';

    protected const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string
     */
    protected static $defaultName = 'cache:report';

    /**
     * @var string
     */
    protected static $defaultDescription = 'View inside the cache.';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::COMMAND_OPTION_TAGS,
                't',
                InputOption::VALUE_NONE,
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
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getDataHeaders(InputInterface $input, OutputInterface $output): array
    {
        $header = ['ID', 'Expires'];

        if ($input->getOption(self::COMMAND_OPTION_MTIME)) {
            $header[] = 'Time modified';
        }

        if ($input->getOption(self::COMMAND_OPTION_TAGS)) {
            $header[] = 'Tags';
        }

        return $header;
    }

    /**
     * {@inheritdoc}
     *
     *  @uses Mage::app()
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function getData(InputInterface $input, OutputInterface $output): array
    {
        if (is_null($this->data)) {
            $this->data = [];

            $cacheInstance = Mage::app()->getCache();

            $filterTag = $input->getOption(self::COMMAND_OPTION_FILTER_TAG);
            if ($filterTag !== null) {
                $cacheIds = $cacheInstance->getIdsMatchingAnyTags([$filterTag]);
            } else {
                $cacheIds = $cacheInstance->getIds();
            }

            /** @var string $filterId */
            $filterId = $input->getOption(self::COMMAND_OPTION_FILTER_ID);
            if ($filterId !== null) {
                // @phpstan-ignore argument.type (@todo SR)
                $cacheIds = array_filter($cacheIds, function ($cacheId) use ($filterId) {
                    return stristr($cacheId, $filterId);
                });
            }

            /** @var string[] $cacheIds */
            foreach ($cacheIds as $cacheId) {
                $metaData = $cacheInstance->getMetadatas($cacheId);

                $row = [
                    'id' => $cacheId,
                    'expire' => date(self::DATE_FORMAT, $metaData['expire'])
                ];

                if ($input->getOption(self::COMMAND_OPTION_MTIME)) {
                    $row['mtime'] = date(self::DATE_FORMAT, $metaData['mtime']);
                }

                if ($input->getOption(self::COMMAND_OPTION_TAGS)) {
                    $row['tags'] = implode(',', $metaData['tags']);
                }

                $this->data[] = $row;
            }
        }

        return $this->data;
    }
}
