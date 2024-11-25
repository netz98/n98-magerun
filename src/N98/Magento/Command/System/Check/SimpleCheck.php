<?php

declare(strict_types=1);

namespace N98\Magento\Command\System\Check;

/**
 * Interface SimpleCheck
 *
 * @package N98\Magento\Command\System\Check
 */
interface SimpleCheck
{
    /**
     * @return void
     */
    public function check(ResultCollection $resultCollection);
}
