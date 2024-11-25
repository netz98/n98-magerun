<?php

declare(strict_types=1);

namespace N98\View;

/**
 * Interface View
 *
 * @package N98\View
 */
interface View
{
    /**
     * @param mixed $value
     */
    public function assign(string $key, $value): View;

    public function render(): string;
}
