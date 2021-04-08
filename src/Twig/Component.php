<?php

namespace App\Twig;

use Twig\Environment;
use function Symfony\Component\String\s;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Component
{
    /**
     * Override if creating "inline" component and just return html.
     */
    public function render(Environment $twig): string
    {
        return $twig->render($this->getComponentTemplate(), ['this' => $this]);
    }

    /**
     * Override to customize component name.
     */
    public static function getComponentName(): string
    {
        return s((new \ReflectionClass(static::class))->getShortName())
            ->snake()
            ->ensureEnd('_component')
            ->before('_component')
        ;
    }

    /**
     * Override to customize component template.
     */
    public function getComponentTemplate(): string
    {
        return \sprintf('components/%s.html.twig', static::getComponentName());
    }

    public function get()
    {
        // noop
        // This is the action that's called when we are simply
        // rendering the component. We do nothing... and then
        // the component will render like normal
    }
}
