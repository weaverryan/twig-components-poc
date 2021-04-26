<?php

namespace App\Twig\Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class LiveProp
{
    private bool $readonly;
    private ?string $hydrateWith;
    private ?string $dehydrateWith;

    public function __construct(array $values)
    {
        $this->readonly = $values['readonly'] ?? false;
        $this->hydrateWith = $values['hydrateWith'] ?? null;
        $this->dehydrateWith = $values['dehydrateWith'] ?? null;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
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
