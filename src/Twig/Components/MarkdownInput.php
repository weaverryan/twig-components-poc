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
    private string $name;

    /**
     * This is considered a "modifiable" property. Manipulating this
     * in the ajax by hand has no adverse effects on the app.
     */
    public string $value = '';

    public function hydrate(array $props)
    {
        // not included in this iteration is the ability to have a more dynamic
        // hydrate method, like:
        // public function hydrate(string $name)
        // where we use Reflection to pass in the correct keys to each argument
        // when we have this, we wouldn't need code like this below, because
        // unless an argument has a default value, we would throw an exception
        // if it's not passed as a prop
        if (!isset($props['name'])) {
            throw new \InvalidArgumentException('Missing "name" prop');
        }

        $this->name = $props['name'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRows(): int
    {
        return max(3, floor(strlen($this->value) / 10));
    }
}
