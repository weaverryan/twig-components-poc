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
    private const EXPOSED_PROP_KEY = 'id';

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

            if ($liveProp->isReadonly()) {
                // readonly properties uses reflection to get value
                $property->setAccessible(true);

                $value = $property->getValue($component);
            } else {
                // writable properties uses property access to get value
                // TODO: improve error message if not readable
                $value = $this->propertyAccessor->getValue($component, $name);
            }

            $dehydratedValue = null;
            if ($method = $liveProp->dehydrateMethod()) {
                // TODO: Error checking
                $dehydratedValue = $component->$method($value);
            } else {
                $dehydratedValue = $this->dehydrateProperty($value);
            }

            if (count($liveProp->exposed()) > 0) {
                $data[$name] = [
                    self::EXPOSED_PROP_KEY => $dehydratedValue,
                ];
                foreach ($liveProp->exposed() as $propertyPath) {
                    $value = $this->propertyAccessor->getValue($component, sprintf('%s.%s', $name, $propertyPath));
                    $data[$name][$propertyPath] = $this->dehydrateProperty($value);
                }
            } else {
                $data[$name] = $dehydratedValue;
            }
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

            $dehydratedValue = $data[$name];
            // if there are exposed keys, then the main value should be hidden
            // in an array under self::EXPOSED_PROP_KEY. But if the value is
            // *not* an array, then use the main value. This could mean that,
            // for example, in a "post.title" situation, the "post" itself was changed.
            if (count($liveProp->exposed()) > 0 && isset($dehydratedValue[self::EXPOSED_PROP_KEY])) {
                $dehydratedValue = $dehydratedValue[self::EXPOSED_PROP_KEY];
                unset($data[$name][self::EXPOSED_PROP_KEY]);
            }

            if ($method = $liveProp->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($dehydratedValue);
            } else {
                $value = $this->hydrateProperty($property, $dehydratedValue);
            }

            foreach ($liveProp->exposed() as $exposedProperty) {
                $propertyPath = $this->transformToArrayPath("{$name}.$exposedProperty");

                if (!$this->propertyAccessor->isReadable($data, $propertyPath)) {
                    continue;
                }

                $this->propertyAccessor->setValue(
                    $value,
                    $exposedProperty,
                    // easy way to read off of the array
                    $this->propertyAccessor->getValue($data, $propertyPath)
                );
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

        // for read-only properties with "exposed" sub-parts,
        // only use the main value
        foreach ($properties as $key => $val) {
            if (\in_array($key, $readonlyProperties) && is_array($val)) {
                $properties[$key] = $val[self::EXPOSED_PROP_KEY];
            }
        }

        // sort so it is always consistent (frontend could have re-ordered data)
        \ksort($properties);

        return \base64_encode(\hash_hmac('sha256', http_build_query($properties), $this->secret, true));
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

    /**
     * Transforms a path like `post.name` into `[post][name]`.
     *
     * This allows us to use the property accessor to find this
     * inside an array.
     *
     * @param string $propertyPath
     * @return string
     */
    private function transformToArrayPath(string $propertyPath): string
    {
        $parts = explode('.', $propertyPath);

        $path = '';
        foreach ($parts as $part) {
            $path .= "[{$part}]";
        }

        return $path;
    }
}
