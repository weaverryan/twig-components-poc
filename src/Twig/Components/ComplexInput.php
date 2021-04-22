<?php

namespace App\Twig\Components;

use App\Twig\Attribute\State;
use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComplexInput extends LiveComponent
{
    /**
     * @State(writable=true)
     */
    public string $value = '';

    /**
     * @State(hydrateWith="hydratePrefixer()", dehydrateWith="dehydratePrefixer")
     */
    public Prefixer $prefixer;

    /**
     * @State
     */
    public \DateTime $date;

    public function mount(string $prefix): void
    {
        $this->prefixer = new Prefixer($prefix);
    }

    public function prefixedValue(): string
    {
        return ($this->prefixer)($this->value);
    }

    public function hydratePrefixer(string $prefix): Prefixer
    {
        return new Prefixer($prefix);
    }

    public function dehydratePrefixer(): string
    {
        return $this->prefixer->prefix();
    }
}
