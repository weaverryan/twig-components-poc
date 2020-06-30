<?php

namespace App\Twig\Components;

use App\Twig\Component;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Input extends Component
{
    public ?string $label = null;
    public ?string $value = null;
    public string $type = 'text';
    public array $errors = [];

    public function setErrors($errors)
    {
        $this->errors = (array) $errors;
    }
}
