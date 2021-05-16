<?php

namespace App\Twig;

use App\Twig\Attribute\LiveAction;
use App\Twig\Attribute\LiveProp;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentHydrator
{
    private const CHECKSUM_KEY = '_checksum';

    private iterable $propertyHydrators;
    private PropertyAccessorInterface $propertyAccessor;
    private Reader $annotationReader;
    private string $secret;

    /**
     * @param PropertyHydrator[] $propertyHydrators
     */
    public function __construct(iterable $propertyHydrators, PropertyAccessorInterface $propertyAccessor, Reader $annotationReader, string $secret)
    {
        $this->propertyHydrators = $propertyHydrators;
        $this->propertyAccessor = $propertyAccessor;
        $this->annotationReader = $annotationReader;
        $this->secret = $secret;
    }

    public function isActionAllowed(LiveComponent $component, string $action): bool
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->annotationReader->getMethodAnnotation($method, LiveAction::class)) {
                return true;
            }
        }

        return false;
    }

    public function dehydrate(LiveComponent $component): array
    {
        $data = [];
        $readonlyProperties = [];

        foreach ($this->reflectionProperties($component) as $property) {
            $liveProp = $this->livePropFor($property);
            $name = $property->getName();

            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $name;
            }

            if ($method = $liveProp->dehydrateMethod()) {
                // TODO: Error checking
                $data[$name] = $component->$method();

                continue;
            }

            if ($liveProp->isReadonly()) {
                // readonly properties uses reflection to get value
                $property->setAccessible(true);

                $value = $property->getValue($component);
            } else {
                // writable properties uses property access to get value
                // TODO: improve error message if not readable
                $value = $this->propertyAccessor->getValue($component, $name);
            }

            $data[$name] = $this->dehydrateProperty($value);
        }

        $data[self::CHECKSUM_KEY] = $this->computeChecksum($data, $readonlyProperties);

        return $data;
    }

    public function hydrate(LiveComponent $component, array $data): void
    {
        $readonlyProperties = [];

        /*
         * Determine readonly properties for checksum verification. We need to do this
         * before setting properties on the component. It is unlikely but there could
         * be security implications to doing it after (component setter's could have
         * side effects).
         */
        foreach ($this->reflectionProperties($component) as $property) {
            if ($this->livePropFor($property)->isReadonly()) {
                $readonlyProperties[] = $property->getName();
            }
        }

        $this->verifyChecksum($data, $readonlyProperties);

        unset($data[self::CHECKSUM_KEY]);

        foreach ($this->reflectionProperties($component) as $property) {
            $liveProp = $this->livePropFor($property);
            $name = $property->getName();

            if (!\array_key_exists($name, $data)) {
                // this property was not sent
                continue;
            }

            if ($method = $liveProp->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($data[$name]);
            } else {
                $value = $this->hydrateProperty($property, $data[$name]);
            }

            foreach ($liveProp->exposed() as $exposedProperty) {
                $propertyPath = "{$name}.$exposedProperty";

                if (\array_key_exists($propertyPath, $data)) {
                    $this->propertyAccessor->setValue($value, $exposedProperty, $data[$propertyPath]);
                }
            }

            if ($liveProp->isReadonly()) {
                // readonly properties uses reflection to set value
                $property->setAccessible(true);

                $property->setValue($component, $value);
            } else {
                // writable properties uses property access to set value
                // TODO: improve error message if not writable
                $this->propertyAccessor->setValue($component, $name, $value);
            }
        }
    }

    private function computeChecksum(array $data, array $readonlyProperties): string
    {
        // filter to only readonly properties
        $properties = array_filter($data, static fn($key) => \in_array($key, $readonlyProperties, true), ARRAY_FILTER_USE_KEY);

        // sort so it is always consistent (frontend could have re-ordered data)
        \ksort($properties);

        // If $data was sent on a request, at this point, it will always all be strings
        // So, normalize to string to prevent a different checksum between
        // a "bool true" (when originally computing) and a string "true" later
        // TODO: maybe this should be normalized before coming here
        $properties = array_map(function($value) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return (string) $value;
        }, $properties);

        return \base64_encode(\hash_hmac('sha256', \json_encode($properties, \JSON_THROW_ON_ERROR), $this->secret, true));
    }

    private function verifyChecksum(array $data, array $readonlyProperties): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $data)) {
            throw new \RuntimeException('No checksum!');
        }

        if (!hash_equals($this->computeChecksum($data, $readonlyProperties), $data[self::CHECKSUM_KEY])) {
            throw new \RuntimeException('Invalid checksum!');
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
        if (\is_scalar($value) || \is_array($value) || \is_null($value)) {
            // nothing to dehydrate...
            return $value;
        }

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
    private function reflectionProperties(object $object): iterable
    {
        $class = $object instanceof \ReflectionClass ? $object : new \ReflectionClass($object);

        foreach ($class->getProperties() as $property) {
            if (null !== $this->livePropFor($property)) {
                yield $property;
            }
        }

        if ($parent = $class->getParentClass()) {
            yield from $this->reflectionProperties($parent);
        }
    }

    private function livePropFor(\ReflectionProperty $property): ?LiveProp
    {
        return $this->annotationReader->getPropertyAnnotation($property, LiveProp::class);
    }
}
