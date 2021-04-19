<?php

namespace App\Twig;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentHydrator
{
    private iterable $propertyHydrators;
    private PropertyAccessorInterface $propertyAccessor;

    /**
     * @param PropertyHydrator[] $propertyHydrators
     */
    public function __construct(iterable $propertyHydrators, PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyHydrators = $propertyHydrators;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function dehydrate(LiveComponent $component): array
    {
        // TODO: allow user to totally take over (via interface on component?)

        $data = [];

        foreach (self::reflectionProperties($component) as $property) {
            // TODO: allow user to take over dehydration on a per-property basis

            if (str_contains((string) $property->getDocComment(), '@WritableState')) {
                // writable state uses property access to get value
                // TODO: improve error message if not readable
                $value = $this->propertyAccessor->getValue($component, $property->getName());
            } else {
                // non-writable state uses reflection to get value
                $value = $property->getValue($component);
            }

            $data[$property->getName()] = $this->dehydrateProperty($value);
        }

        // TODO: calculate checksum

        return $data;
    }

    public function hydrate(LiveComponent $component, array $data): void
    {
        // TODO: allow user to totally take over (via interface on component?)

        // TODO: verify checksum

        foreach (self::reflectionProperties($component) as $property) {
            $name = $property->getName();

            if (!\array_key_exists($name, $data)) {
                // this property was not sent
                continue;
            }

            $value = $this->hydrateProperty($property, $data[$name]);

            // TODO: allow user to take over hydration on a per-property basis

            if (str_contains((string) $property->getDocComment(), '@WritableState')) {
                // writable state uses property access to set value
                // TODO: improve error message if not writable
                $this->propertyAccessor->setValue($component, $name, $value);
            } else {
                $property->setValue($component, $value);
            }
        }
    }

    /**
     * @param scalar|null|array $value
     *
     * @return mixed
     */
    private function hydrateProperty(\ReflectionProperty $property, $value)
    {
        if (!$property->getType() || !$property->getType() instanceof \ReflectionNamedType || $property->getType()->isBuiltin()) {
            return $value;
        }

        foreach ($this->propertyHydrators as $hydrator) {
            try {
                return $hydrator->hydrate($property->getType()->getName(), $value);
            } catch (UnsupportedHydrationException $e) {
                continue;
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return scalar|null|array
     */
    private function dehydrateProperty($value)
    {
        foreach ($this->propertyHydrators as $hydrator) {
            try {
                $value = $hydrator->dehydrate($value);

                break;
            } catch (UnsupportedHydrationException $e) {
                continue;
            }
        }

        if (!\is_scalar($value) && !\is_array($value) && !\is_null($value)) {
            // TODO: more context for exception (component class and property)
            throw new \LogicException(\sprintf('Cannot dehydrate "%s".', get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param \ReflectionClass|object $object
     *
     * @return \ReflectionProperty[]
     */
    private static function reflectionProperties(object $object): iterable
    {
        $class = $object instanceof \ReflectionClass ? $object : new \ReflectionClass($object);

        foreach ($class->getProperties() as $property) {
            // TODO: use real annotation/attribute
            $doc = (string) $property->getDocComment();

            if (str_contains($doc, '@WritableState')) {
                yield $property;

                continue;
            }

            if (str_contains($doc, '@State')) {
                // ensure non-writable state is accessible
                $property->setAccessible(true);

                yield $property;
            }
        }

        if ($parent = $class->getParentClass()) {
            yield from self::reflectionProperties($parent);
        }
    }
}
