<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentFactory
{
    private ServiceLocator $components;
    private ComponentDataAccessor $dataAccessor;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(ServiceLocator $components, ComponentDataAccessor $dataAccessor)
    {
        $this->components = $components;
        $this->dataAccessor = $dataAccessor;
    }

    public function create(string $name, array $props): Component
    {
        // we clone here to ensure we don't modify state of the object in the DI container
        /** @var Component $component */
        $component = clone $this->components->get($name);

        $this->hydrate($component, $props);

        // store the original props for later
        if ($component instanceof LiveComponent) {
            $component->setProps($props);
        }

        /*
         * This is for convenience.
         *
         * This is mostly here for non-live components, where everything has public
         * values. In that case, we don't want to require you to need to create a
         * hydrate() method where you manually read in the $props and set each
         * on to the corresponding public property. That's why we pass the props
         * to `hydrate()`... but then we also assign the props to any matching
         * public properties.
         */
        $this->dataAccessor->writeData($component, $props);

        return $component;
    }

    private function hydrate(Component $component, array $props): void
    {
        try {
            $refMethod = (new \ReflectionClass($component))->getMethod('hydrate');
        } catch (\ReflectionException $e) {
            // no hydrate method
            return;
        }

        $parameters = [];

        foreach ($refMethod->getParameters() as $refParameter) {
            // TODO: "transformers" (e.g. id => entity object)
            if (isset($props[$refParameter->getName()])) {
                $parameters[] = $props[$refParameter->getName()];

                continue;
            }

            // TODO: error checking!
        }

        $component->hydrate(...$parameters);
    }
}
