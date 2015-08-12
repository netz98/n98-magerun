<?php

namespace N98\Magento\Command\System\Check;

interface SimpleCheck
{
    /**
     * @param ResultCollection $results
     */
    public function check(ResultCollection $results);
}