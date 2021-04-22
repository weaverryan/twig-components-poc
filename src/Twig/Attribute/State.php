<?php

namespace App\Twig\Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class State
{
    private bool $writable;

    public function __construct(array $values)
    {
        $this->writable = $values['writable'] ?? false;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }
}
