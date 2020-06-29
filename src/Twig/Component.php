<?php

namespace App\Twig;

use function Symfony\Component\String\s;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Component
{
    public static function getName(): string
    {
        return s((new \ReflectionClass(static::class))->getShortName())->snake();
    }

    public static function getTemplateName(): string
    {
        return \sprintf('components/%s.html.twig', static::getName());
    }
}
