<?php

namespace N98\View;

interface View
{
    /**
     * @param string $key
     * @param mixed $value
     *
     * @return View
     */
    public function assign($key, $value);

    /**
     * @return string
     */
    public function render();
}
