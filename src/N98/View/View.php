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
     * @param string $key
     * @param mixed $value
     *
     * @return View
     */
    public function assign(string $key, $value): View;

    /**
     * @return string
     */
    public function render(): string;
}
