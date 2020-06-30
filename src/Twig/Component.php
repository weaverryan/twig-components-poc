<?php

namespace App\Twig;

use function Symfony\Component\String\s;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Component
{
    public static function getComponentName(): string
    {
        return s((new \ReflectionClass(static::class))->getShortName())->snake();
    }

    public static function getComponentTemplate(): string
    {
        return \sprintf('components/%s.html.twig', static::getComponentName());
    }
}
