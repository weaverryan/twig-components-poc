<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentRegistry
{
    private static ?PropertyAccessor $propertyAccessor = null;
    private $twig;
    private $components;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(Environment $twig, ServiceLocator $components)
    {
        $this->twig = $twig;
        $this->components = $components;
    }

    public function render(string $name, array $context): string
    {
        /** @var Component $component */
        $component = clone $this->components->get($name);

        self::addContextToComponent($component, $context);

        return $this->twig->render($component::getTemplateName(), [
            'this' => $component,
            'attributes' => new AttributeBag($context),
//            'slots' => todo
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
