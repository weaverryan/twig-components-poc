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
    private array $serviceIdMap;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(ServiceLocator $components, PropertyAccessorInterface $propertyAccessor, array $serviceIdMap)
    {
        $this->components = $components;
        $this->propertyAccessor = $propertyAccessor;
        $this->serviceIdMap = $serviceIdMap;
    }

    public function serviceIdFor(string $name): string
    {
        if (!isset($this->serviceIdMap[$name])) {
            throw new \InvalidArgumentException('Component not found.');
        }

        return $this->serviceIdMap[$name];
    }

    /**
     * Creates the component and "mounts" it with the passed data.
     */
    public function create(string $name, array $data): Component
    {
        $component = clone $this->components->get($name);

        $this->mount($component, $data);

        // set data that wasn't set in mount on the component directly
        foreach ($data as $property => $value) {
            if (!$this->propertyAccessor->isWritable($component, $property)) {
                throw new \LogicException(\sprintf('Unable to write "%s" to component "%s".', $property, \get_class($component)));
            }

            $this->propertyAccessor->setValue($component, $property, $value);
        }

        return $component;
    }

    private function mount(Component $component, array &$data): void
    {
        try {
            $method = (new \ReflectionClass($component))->getMethod('mount');
        } catch (\ReflectionException $e) {
            // no hydrate method
            return;
        }

        $parameters = [];

        foreach ($method->getParameters() as $refParameter) {
            $name = $refParameter->getName();

            if (\array_key_exists($name, $data)) {
                $parameters[] = $data[$name];

                // remove the data element so it isn't used to set the property directly.
                unset($data[$name]);
            }
        }

        $component->mount(...$parameters);
    }
}
