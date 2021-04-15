<?php

namespace App\Twig\Components;

use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MarkdownInput extends LiveComponent
{
    /**
     * TODO: mark as state
     * @state
     */
    public string $name;

    /**
     * TODO: mark as state
     * @state
     */
    public string $label;

    /**
     * TODO: mark as writable/bindable state
     * @state
     */
    public string $value = '';

    public function mount(string $name): void
    {
        $this->name = $name;
        $this->label = ucfirst($name);
    }

    public function getRows(): int
    {
        return max(3, floor(strlen($this->value) / 10));
    }
}
