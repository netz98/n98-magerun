<?php

declare(strict_types=1);

namespace N98\View;

/**
 * Class PhpView
 *
 * @package N98\View
 */
class PhpView implements View
{
    protected array $vars = [];

    protected string $template;

    public function setTemplate(string $template): PhpView
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param mixed $value
     */
    public function assign(string $key, $value): PhpView
    {
        $this->vars[$key] = $value;
        return $this;
    }

    public function render(): string
    {
        extract($this->vars);
        ob_start();
        include $this->template;
        $content = (string) ob_get_contents();
        ob_end_clean();

        return $content;
    }

    protected function xmlProlog(): string
    {
        return '<?xml version="1.0"?>' . "\n";
    }
}
