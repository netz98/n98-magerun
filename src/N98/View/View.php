<?php

namespace N98\View;

interface View
{
    public function assign($key, $value);
    public function render();
}
