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
    private ComponentRegistry $registry;

    public function __construct(ComponentRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }

    public function renderComponent(Environment $env, string $name, array $props = []): string
    {
        $component = $this->registry->get($name, $props);
        $rendered = $component->render($env);

        if (!$component instanceof LiveComponent) {
            return $rendered;
        }

        return $env->render('components/live_component.html.twig', [
            'component' => $component,
            'name' => $name,
            // TODO - need transformer system to convert data objects
            // for example, converting an Entity object to an id
            // We also need this in LiveComponentSubscriber::onKernelResponse
            'data' => $component->getData(),
            'props' => $component->getProps(),
            'rendered' => $rendered,
        ]);
    }
}
