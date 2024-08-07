<?php

declare(strict_types=1);

namespace N98\Magento\Command;

/**
 * Interface CommandAware
 *
 * @package N98\Magento\Command
 */
interface CommandFormatable
{
    /**
     * @return string[]
     */
    public function getListHeader(): array;

    /**
     * @return array
     */
    public function getListData(): array;

    /**
     * @return string
     */
    public function getSectionTitle(): string;
}
