<?php

namespace App\Twig\Components;

use App\Twig\LiveComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MarkdownInput extends LiveComponent
{
    /**
     * This is considered a "readonly" property. We don't want the
     * possibility of this being manipulated during the component's
     * lifecycle as it affects the field being updated on the
     * form submit.
     */
    public string $name;

    /**
     * This is considered a "modifiable" property. Manipulating this
     * in the ajax by hand has no adverse effects on the app.
     */
    public string $value = '';

    public function getRows(): int
    {
        return max(3, floor(strlen($this->value) / 10));
    }
}
