<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentRegistry
{
    private static ?PropertyAccessor $propertyAccessor = null;

    private ServiceLocator $components;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(ServiceLocator $components)
    {
        $this->components = $components;
    }

    public function get(string $name, array $state): Component
    {
        // we clone here to ensure we don't modify state of the object in the DI container
        $component = clone $this->components->get($name);

        foreach ($state as $property => $value) {
            self::propertyAccessor()->setValue($component, $property, $value);
        }

        return $component;
    }

    private static function propertyAccessor(): PropertyAccessor
    {
        return self::$propertyAccessor ?: self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
    }
}
