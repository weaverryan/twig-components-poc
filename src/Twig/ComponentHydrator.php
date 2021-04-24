<?php

namespace App\Twig;

use App\Twig\Attribute\State;
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

    public function dehydrate(LiveComponent $component): array
    {
        $data = [];
        $stateProperties = [];

        foreach ($this->reflectionProperties($component) as $property) {
            $state = $this->stateFor($property);
            $name = $property->getName();

            if (!$state->isWritable()) {
                $stateProperties[] = $name;
            }

            if ($method = $state->dehydrateMethod()) {
                // TODO: Error checking
                $data[$name] = $component->$method();

                continue;
            }

            if ($state->isWritable()) {
                // writable state uses property access to get value
                // TODO: improve error message if not readable
                $value = $this->propertyAccessor->getValue($component, $name);
            } else {
                // non-writable state uses reflection to get value
                $property->setAccessible(true);

                $value = $property->getValue($component);
            }

            $data[$name] = $this->dehydrateProperty($value);
        }

        $data[self::CHECKSUM_KEY] = $this->computeChecksum($data, $stateProperties);

        return $data;
    }

    public function hydrate(LiveComponent $component, array $data): void
    {
        $stateProperties = [];

        /*
         * Determine state properties for checksum verification. We need to do this
         * before setting properties on the component. It is unlikely but there could
         * be security implications to doing it after (component setter's could have
         * side effects).
         */
        foreach ($this->reflectionProperties($component) as $property) {
            if (!$this->stateFor($property)->isWritable()) {
                $stateProperties[] = $property->getName();
            }
        }

        $this->verifyChecksum($data, $stateProperties);

        unset($data[self::CHECKSUM_KEY]);

        foreach ($this->reflectionProperties($component) as $property) {
            $state = $this->stateFor($property);
            $name = $property->getName();

            if (!\array_key_exists($name, $data)) {
                // this property was not sent
                continue;
            }

            if ($method = $state->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($data[$name]);
            } else {
                $value = $this->hydrateProperty($property, $data[$name]);
            }

            if ($state->isWritable()) {
                // writable state uses property access to set value
                // TODO: improve error message if not writable
                $this->propertyAccessor->setValue($component, $name, $value);
            } else {
                $property->setAccessible(true);

                $property->setValue($component, $value);
            }
        }
    }

    private function computeChecksum(array $data, array $stateProperties): string
    {
        // filter to only state properties
        $state = array_filter($data, static fn($key) => \in_array($key, $stateProperties, true), ARRAY_FILTER_USE_KEY);

        // sort so it is always consistent (frontend could have re-ordered data)
        \ksort($state);

        return \base64_encode(\hash_hmac('sha256', \json_encode($state, \JSON_THROW_ON_ERROR), $this->secret, true));
    }

    private function verifyChecksum(array $data, array $stateProperties): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $data)) {
            throw new \RuntimeException('No checksum!');
        }

        if (!hash_equals($this->computeChecksum($data, $stateProperties), $data[self::CHECKSUM_KEY])) {
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
            if (null !== $this->stateFor($property)) {
                yield $property;
            }
        }

        if ($parent = $class->getParentClass()) {
            yield from $this->reflectionProperties($parent);
        }
    }

    private function stateFor(\ReflectionProperty $property): ?State
    {
        return $this->annotationReader->getPropertyAnnotation($property, State::class);
    }
}
