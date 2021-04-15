<?php

namespace App\Twig;

use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentExtension extends AbstractExtension
{
    private ComponentFactory $factory;
    private SerializerInterface $serializer;

    public function __construct(ComponentFactory $factory, SerializerInterface $serializer)
    {
        $this->factory = $factory;
        $this->serializer = $serializer;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'renderComponent'], ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }

    public function renderComponent(Environment $env, string $name, array $props = []): string
    {
        $component = $this->factory->createAndMount($name, $props);
        $rendered = $component->render($env);

        if (!$component instanceof LiveComponent) {
            return $rendered;
        }

        // TODO: this serializes methods w/o properties - how to avoid
        // TODO: our own serializer/dehydrator
        $serialized = $this->serializer->serialize($component, 'json');

        return $env->render('components/live_component.html.twig', [
            'component' => $component,
            'name' => $name,
            'data' => $serialized,
            'rendered' => $rendered,
        ]);
    }
}
