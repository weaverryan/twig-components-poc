<?php

namespace App\Twig\Components;

use App\Twig\Component;

/**
 * Imagine this data comes from a database or something...
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DataTable extends Component
{
    public ?string $caption = null;

    public function headers(): array
    {
        return ['a', 'b', 'c'];
    }

    public function data(): array
    {
        return [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '7', '9'],
        ];
    }
}
