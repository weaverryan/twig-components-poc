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

    public function setErrors($errors): void
    {
        // demonstrate that setting properties can be done via a setter if some logic is required
        $this->errors = (array) $errors;
    }

    public function hasErrors(): bool
    {
        // can call methods from the twig template
        return \count($this->errors);
    }
}
