<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface PropertyHydrator
{
    /**
     * @param mixed $value
     *
     * @return scalar|null|array
     *
     * @throws UnsupportedHydrationException If unable to dehydrate.
     */
    public function dehydrate($value);

    /**
     * @param scalar|null|array $value
     *
     * @return mixed
     *
     * @throws UnsupportedHydrationException If unable to dehydrate.
     */
    public function hydrate(string $type, $value);
}
