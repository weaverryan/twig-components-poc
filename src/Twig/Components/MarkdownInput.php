<?php

namespace App\Twig\Components;

use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MarkdownInput extends LiveComponent
{
    /**
     * @State
     */
    public string $name;

    /**
     * @State
     */
    public string $label;

    /**
     * @WritableState
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
