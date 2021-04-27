<?php

namespace App\Twig\Components;

use App\Twig\Attribute\LiveProp;
use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Date extends LiveComponent
{
    /**
     * @LiveProp(readonly=true)
     */
    private \DateTimeInterface $created;

    public function mount(\DateTimeInterface $created = null): void
    {
        $this->created = $created ?? new \DateTime('now');
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created;
    }
}
