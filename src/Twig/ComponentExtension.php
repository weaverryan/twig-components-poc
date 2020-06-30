<?php

namespace App\Twig;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Extension\AbstractExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtension extends AbstractExtension
{
    private static ?PropertyAccessor $propertyAccessor = null;
    private ComponentRegistry $registry;

    public function __construct(ComponentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getTokenParsers(): array
    {
        return [new ComponentTokenParser($this->registry)];
    }

    public function getComponentContext(string $name, array $with, array $context): array
    {
        $component = clone $this->registry->get($name);

        self::addContextToComponent($component, $with);

        return \array_merge($context, [
            'this' => $component,
            'attributes' => new AttributeBag($with),
        ]);
    }

    private static function addContextToComponent(Component $component, array &$context): void
    {
        foreach ($context as $property => $value) {
            try {
                self::propertyAccessor()->setValue($component, $property, $value);
                unset($context[$property]);
            } catch (NoSuchPropertyException $e) {
                continue;
            }
        }
    }

    private static function propertyAccessor(): PropertyAccessor
    {
        return self::$propertyAccessor ?: self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
    }
}
