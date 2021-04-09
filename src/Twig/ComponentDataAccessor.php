<?php

namespace App\Twig;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ComponentDataAccessor
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Reads & returns the publicly modifiable data as scalars.
     *
     * The data returned from this can be serialized for the frontend.
     */
    public function readData(Component $component): array
    {
        // TODO: transformer system to go from Object -> Scalar
        $data = [];
        foreach ($this->getDataProperties($component) as $property) {
            $data[$property] = $this->propertyAccessor->getValue($component, $property);
        }

        return $data;
    }

    /**
     * Sets publicly modifiable data onto the component.
     *
     * This accepts scalar formats from the frontend and will transform
     * those into objects, as necessary.
     */
    public function writeData(Component $component, array $data): void
    {
        // TODO: transformer system to go from Scalar -> Object
        foreach ($this->getDataProperties($component) as $property) {
            if (array_key_exists($property, $data)) {
                $this->propertyAccessor->setValue($component, $property, $data[$property]);
            }
        }
    }

    private function getDataProperties(Component $component): array
    {
        $reflectionObject = new \ReflectionObject($component);
        $dataProperties = [];
        foreach ($reflectionObject->getProperties() as $property) {
            if ($property->isPublic()) {
                $dataProperties[] = $property->getName();
            }
        }

        return $dataProperties;
    }
}
