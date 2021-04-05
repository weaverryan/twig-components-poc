<?php

namespace App\Twig;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtension extends AbstractExtension
{
    private const ATTRIBUTES_KEY = '_attributes';

    private static ?PropertyAccessor $propertyAccessor = null;
    private ComponentRegistry $registry;

    public function __construct(ComponentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new TwigFunction('attributes', [self::class, 'renderAttributes'], ['needs_context' => true, 'is_safe' => ['all']]),
        ];
    }

    public function getComponentContext(string $name, array $with, array $context): array
    {
        $component = clone $this->registry->get($name);

        self::addContextToComponent($component, $with);

        return \array_merge($context, [
            'this' => $component,
            self::ATTRIBUTES_KEY => new AttributeBag($with),
        ]);
    }

    public function renderComponent(Environment $env, array $context, string $name, array $with = [], bool $withContext = true): string
    {
        $context = $this->getComponentContext($name, $with, $withContext ? $context : []);

        return $env->resolveTemplate($context['this']::getComponentTemplate())->render($context);
    }

    public static function renderAttributes(array $context, array $with = []): string
    {
        if (!isset($context[self::ATTRIBUTES_KEY])) {
            throw new \RuntimeException('Cannot use attributes function outside of component scope.');
        }

        return $context[self::ATTRIBUTES_KEY]->merge($with);
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
