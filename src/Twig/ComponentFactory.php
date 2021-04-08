<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentFactory
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

    public function create(string $name, array $props, array $state = []): Component
    {
        // we clone here to ensure we don't modify state of the object in the DI container
        /** @var Component $component */
        $component = clone $this->components->get($name);

        // TODO: smarter system where we read the argument names
        // and maybe even transform them (e.g. id => entity object)
        $component->hydrate($props);

        // store the original props for later
        if ($component instanceof LiveComponent) {
            $component->setProps($props);
        }

        /*
         * This first foreach is for convenience... but it *does* look a little odd.
         *
         * This is mostly here for non-live components, where everything has public
         * values. In that case, we don't want to require you to need to create a
         * hydrate() method where you manually read in the $props and set each
         * on to the corresponding public property. That's why we pass the props
         * to `hydrate()`... but then we also assign (as a default value) any
         * public properties.
         *
         * Maybe there is a nicer way to do this... like a protected helper method
         * in Component that you could call from hydrate() to assign public properties
         * in mass.
         */
        foreach ($props as $property => $value) {
            // TODO: per the current plan, this should ONLY set public properties,
            // not also use setters, etc.
            if ($this->propertyAccessor->isWritable($component, $property)) {
                $this->propertyAccessor->setValue($component, $property, $value);
            }
        }

        foreach ($state as $property => $value) {
            // TODO: per the current plan, this should ONLY set public properties,
            // not also use setters, etc.
            if ($this->propertyAccessor->isWritable($component, $property)) {
                $this->propertyAccessor->setValue($component, $property, $value);
            }
        }

        return $component;
    }
}
