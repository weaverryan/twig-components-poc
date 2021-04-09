<?php

namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtension extends AbstractExtension
{
    private ComponentFactory $factory;
    private ComponentDataAccessor $dataAccessor;

    public function __construct(ComponentFactory $factory, ComponentDataAccessor $dataAccessor)
    {
        $this->factory = $factory;
        $this->dataAccessor = $dataAccessor;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }

    public function renderComponent(Environment $env, string $name, array $props = []): string
    {
        $component = $this->factory->create($name, $props);
        $rendered = $component->render($env);

        if (!$component instanceof LiveComponent) {
            return $rendered;
        }

        return $env->render('components/live_component.html.twig', [
            'component' => $component,
            'name' => $name,
            'data' => $this->dataAccessor->readData($component),
            'props' => $component->getProps(),
            'rendered' => $rendered,
        ]);
    }
}
