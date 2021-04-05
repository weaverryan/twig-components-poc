<?php

namespace App\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use function Symfony\Component\String\s;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Component
{
    private static ?PropertyAccessor $propertyAccessor = null;

    /**
     * todo should this be offloaded to a service? should/can the serializer be used?
     *
     * @return static
     */
    final public function injectContext(array $context): self
    {
        foreach ($context as $property => $value) {
            self::propertyAccessor()->setValue($this, $property, $value);
        }

        return $this;
    }

    public static function getComponentName(): string
    {
        return s((new \ReflectionClass(static::class))->getShortName())
            ->snake()
            ->ensureEnd('_component')
            ->before('_component')
        ;
    }

    public static function getComponentTemplate(): string
    {
        return \sprintf('components/%s.html.twig', static::getComponentName());
    }

    private static function propertyAccessor(): PropertyAccessor
    {
        return self::$propertyAccessor ?: self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
    }
}
