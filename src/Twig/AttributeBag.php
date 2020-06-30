<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AttributeBag
{
    private array $attributes;

    /**
     * @param array<string, string> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function __toString(): string
    {
        $ret = '';

        foreach ($this->attributes as $key => $value) {
            $ret .= " {$key}=\"{$value}\"";
        }

        return $ret;
    }

    /**
     * @param array<string, string> $with
     */
    public function merge(array $with): self
    {
        foreach ($this->attributes as $key => $value) {
            $with[$key] = isset($with[$key]) ? "{$with[$key]} {$value}" : $value;
        }

        return new self($with);
    }
}
