<?php

namespace App\Twig;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentHydrator
{
    private const CHECKSUM_KEY = '_checksum';

    public function dehydrate(LiveComponent $component): array
    {
        // TODO: allow user to totally take over (via interface on component?)

        $data = [];

        foreach (self::reflectionProperties($component) as $property) {
            // TODO: "bindable" state should be set via property access
            $property->setAccessible(true);
            // TODO: run through "property dehydrators"
            $data[$property->getName()] = $property->getValue($component);
        }

        // TODO: calculate checksum
        $data[self::CHECKSUM_KEY] = 'todo';

        return $data;
    }

    public function hydrate(LiveComponent $component, array $data): void
    {
        // TODO: verify checksum
        unset($data[self::CHECKSUM_KEY]);

        foreach (self::reflectionProperties($component) as $property) {
            $name = $property->getName();

            if (!\array_key_exists($name, $data)) {
                throw new \RuntimeException(\sprintf('Unable to hydrate "%s::$%s" - data was not sent.', \get_class($component), $name));
            }

            $property->setAccessible(true);
            // TODO: run through "property hydrators"
            $property->setValue($component, $data[$name]);
        }
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
