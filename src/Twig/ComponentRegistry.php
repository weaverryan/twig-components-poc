<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentRegistry
{
    private ServiceLocator $components;
    private PropertyAccessorInterface $propertyAccessor;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(ServiceLocator $components, PropertyAccessorInterface $propertyAccessor)
    {
        $this->components = $components;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function get(string $name, array $state): Component
    {
        // we clone here to ensure we don't modify state of the object in the DI container
        $component = clone $this->components->get($name);

        foreach ($state as $property => $value) {
            $this->propertyAccessor->setValue($component, $property, $value);
        }

        return $component;
    }
}
