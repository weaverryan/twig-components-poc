<?php

namespace App\Twig\Components;

use App\Twig\Attribute\LiveProp;
use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MarkdownInput extends LiveComponent
{
    /**
     * @LiveProp(readonly=true)
     */
    public string $name;

    /**
     * @LiveProp(readonly=true)
     */
    public string $label;

    /**
     * @LiveProp
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
