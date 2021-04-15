<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentHydrator
{
    private const CHECKSUM_KEY = '_checksum';

    private iterable $propertyHydrators;

    /**
     * @param PropertyHydrator[] $propertyHydrators
     */
    public function __construct(iterable $propertyHydrators)
    {
        $this->propertyHydrators = $propertyHydrators;
    }

    public function dehydrate(LiveComponent $component): array
    {
        // TODO: allow user to totally take over (via interface on component?)

        $data = [];

        foreach (self::reflectionProperties($component) as $property) {
            // TODO: allow user to take over dehyrdation on a per-property basis
            $property->setAccessible(true);
            $data[$property->getName()] = $this->dehydrateProperty($property->getValue($component));
        }

        // TODO: calculate checksum
        $data[self::CHECKSUM_KEY] = 'todo';

        return $data;
    }

    public function hydrate(LiveComponent $component, array $data): void
    {
        // TODO: allow user to totally take over (via interface on component?)

        // TODO: verify checksum
        unset($data[self::CHECKSUM_KEY]);

        foreach (self::reflectionProperties($component) as $property) {
            $name = $property->getName();

            if (!\array_key_exists($name, $data)) {
                throw new \RuntimeException(\sprintf('Unable to hydrate "%s::$%s" - data was not sent.', \get_class($component), $name));
            }

            // TODO: allow user to take over hyrdation on a per-property basis
            // TODO: "bindable" state should be set via property access
            $property->setAccessible(true);
            $property->setValue($component, $this->hydrateProperty($property, $data[$name]));
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
            } catch (HydrationException $e) {
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
            } catch (HydrationException $e) {
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
            if (str_contains((string) $property->getDocComment(), '@state')) {
                yield $property;
            }
        }

        if ($parent = $class->getParentClass()) {
            yield from self::reflectionProperties($parent);
        }
    }
}
