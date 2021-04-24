<?php

namespace App\Twig\Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class State
{
    private bool $writable;
    private ?string $hydrateWith;
    private ?string $dehydrateWith;

    public function __construct(array $values)
    {
        $this->writable = $values['writable'] ?? false;
        $this->hydrateWith = $values['hydrateWith'] ?? null;
        $this->dehydrateWith = $values['dehydrateWith'] ?? null;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function hydrateMethod(): ?string
    {
        return $this->hydrateWith ? trim($this->hydrateWith, '()') : null;
    }

    public function dehydrateMethod(): ?string
    {
        return $this->dehydrateWith ? trim($this->dehydrateWith, '()') : null;
    }
}
