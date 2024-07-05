<?php

declare(strict_types=1);

namespace N98\View;

use RuntimeException;

/**
 * Class PhpView
 *
 * @package N98\View
 */
class PhpView implements View
{
    /**
     * @var array<string, mixed>
     */
    protected array $vars = [];

    /**
     * @var string
     */
    protected string $template;

    /**
     * @param string $template
     * @return PhpView
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return PhpView
     */
    public function assign(string $key, $value): self
    {
        $this->vars[$key] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        extract($this->vars);
        ob_start();
        include $this->template;
        $content = ob_get_contents();
        ob_end_clean();

        if (!$content) {
            throw new RuntimeException('Template could not be parsed.');
        }

        return $content;
    }

    /**
     * @return string
     */
    protected function xmlProlog(): string
    {
        return '<?xml version="1.0"?>' . "\n";
    }
}
