<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentRegistry
{
    private $components;

    /**
     * @param Component[]|ServiceLocator $components
     */
    public function __construct(ServiceLocator $components)
    {
        $this->components = $components;
    }

    public function get(string $name): Component
    {
        return $this->components->get($name);
    }
}
