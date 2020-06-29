<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AttributeBag
{
    private array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function __toString(): string
    {
        // todo
    }

    public function merge(array $with): self
    {
        // todo deep merge...
    }
}
