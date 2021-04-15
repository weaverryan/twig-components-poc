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

    /**
     * Creates the component and "mounts" it with the passed data.
     */
    public function mount(string $name, array $data): Component
    {
        $component = $this->get($name);

        foreach ($data as $property => $value) {
            // TODO: use Component::mount() if available
            if (!$this->propertyAccessor->isWritable($component, $property)) {
                throw new \LogicException(\sprintf('Unable to write "%s" to component "%s".', $property, \get_class($component)));
            }

            $this->propertyAccessor->setValue($component, $property, $value);
        }

        return $component;
    }

    /**
     * Creates the component and returns it in an "unmounted" state.
     */
    public function get(string $name): Component
    {
        // we clone here to ensure we don't modify state of the object in the DI container
        return clone $this->components->get($name);
    }
}
